<?php
/**
 * @file   BaseNewsSelecter.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Thu Jun 30 13:49:16 2016
 * 
 * @brief  
 * 
 * 
 */

define('MIN_NEWS_COUNT', 8);
define('MAX_NEWS_COUNT', 10);
define('POPULAR_NEWS_CNT', 2);

class Selector10001 extends BaseNewsSelector{

    public function getPolicyTag(){
        $abService = $this->di->get('abtest');
        $experiment = 'channel_' . $this->channel_id . '_strategy';
        $tag = $abService->getTag($experiment);
        if (!in_array($tag, array("10001_popularRanking", 
                                 "10001_lrRanker",
                                 "10001_editorRec"))) {
            $tag = "10001_personalTopicRec";
        }
        # switch for lr model update
        $cache = $this->di->get('cache');
        $isTopicAlg = $cache->get(ALG_LR_SWITCH_KEY);
        if (empty($isTopicAlg) and ($tag=="10001_lrRanker")) {
            $tag = "10001_personalTopicRec";
        }
        return $tag;
    }

    public function emergence($sample_count, $recNewsLst, $options, $prefer) {
        $randomPolicy = new ExpDecayListPolicy($this->di); 
        $randomNewsLst = $randomPolicy->sampling($this->channel_id, 
                $this->device_id, $this->user_id, $sample_count, 
                3, $prefer, $options);
        foreach ($randomNewsLst as $randomNews) {
            if (count($recNewsLst) >= $sample_count) {
                break;
            }
            if (in_array($randomNews, $recNewsLst)) {
                continue;
            }
            $recNewsLst[] = $randomNews;
        }
        return $recNewsLst; 
    }

    public function sampling($sample_count, $prefer) {
        $options = array();
        if ($prefer == "later") {
            $options["long_tail_weight"] = 0;
        }
        // divide whole user into two group, one combine popular & recommend, 
        // the other one only contain popular list 
        $strategyTag = $this->getPolicyTag();
        $popularPolicy = new PopularListPolicy($this->di); 
        $personalTopicPolicy = new PersonalTopicInterestPolicy($this->di);
        $recNewsLst = array();
        $logger = $this->di->get('logger');
        if ($strategyTag == "10001_popularRanking") {
            $recNewsLst = $popularPolicy->sampling($this->channel_id, 
                $this->device_id, $this->user_id, $sample_count, 
                3, $prefer, $options);
        } elseif ($strategyTag == "10001_personalTopicRec") {
            $recNewsLst = $personalTopicPolicy->sampling(
                $this->channel_id, $this->device_id, $this->user_id,
                $sample_count - POPULAR_NEWS_CNT, 3, $prefer, $options);
            if (count($recNewsLst) < $sample_count) {
                $popNewsLst = $popularPolicy->sampling($this->channel_id, 
                    $this->device_id, $this->user_id, $sample_count, 
                    3, $prefer, $options);
                foreach ($popNewsLst as $popNews) {
                    if (count($recNewsLst) >= $sample_count) {
                        break;
                    }
                    if (in_array($popNews, $recNewsLst)) {
                        continue;
                    }
                    $recNewsLst[] = $popNews;
                }
            } 
        } elseif ($strategyTag == "10001_editorRec") {
            $editorRecPolicy = new EditorRecPolicy($this->di); 
            $editorRecNewsCnt = 5;
            $recNewsLst = $editorRecPolicy->sampling(
                $this->channel_id, $this->device_id, $this->user_id,
                $editorRecNewsCnt, 3, $prefer, $options);
            $popularNewsCnt = max(0, $sample_count - $editorRecNewsCnt);
            $popularNewsLst = $popularPolicy->sampling(
                $this->channel_id, $this->device_id, $this->user_id, 
                $popularNewsCnt, 3, $prefer,  $options);
            foreach ($popularNewsLst as $curNewsId) {
                if (!in_array($curNewsId, $recNewsLst)) {
                    $recNewsLst[] = $curNewsId;
                }
            }
        } else {
            // combine popular & topic recommend recall with rerank
            $recNewsLst = $popularPolicy->sampling($this->channel_id, 
                $this->device_id, $this->user_id, 50, 3, $prefer, 
                $options);
            $topicNewsLst = $personalTopicPolicy->sampling(
                $this->channel_id, $this->device_id, $this->user_id,
                10, 3, $prefer, $options);
            // merge news from different strategy without duplicate
            foreach ($topicNewsLst as $curNewsId) {
                if (!in_array($curNewsId, $recNewsLst)) {
                    $recNewsLst[] = $curNewsId;
                }
            }
            if (count($recNewsLst) == 0) {
                $recNewsLst = $this->emergence(30, $recNewsLst, 
                    $options, $prefer);
            }
        }

        if (count($recNewsLst) < $sample_count) {
            $recNewsLst = $this->emergence($sample_count, 
                $recNewsLst, $options, $prefer);
        }
        return $recNewsLst;
    }

    public function select($prefer) {
        $sample_count = mt_rand(MIN_NEWS_COUNT, MAX_NEWS_COUNT);
        $selected_news_list = $this->sampling($sample_count, 
            $prefer);
        $selected_news_list = $this->newsFilter($selected_news_list);
        $newsObjDct = News::BatchGet($selected_news_list);
        $newsObjDct = $this->removeInvisible($newsObjDct);
        $newsObjDct = $this->removeDup($newsObjDct);
        $strategyTag = $this->getPolicyTag();
        $logger->info(sprintf("[di:%s][channel:%s][strategy:%s][recall news:%s]",
            $this->device_id, $this->channel_id, $strategyTag, 
            count($newsObjDct)));

        // rerank news from recall step
        $newsFeatureDct = array();
        $cache = $this->di->get('cache');
        $isLrRanker = $cache->get(ALG_LR_SWITCH_KEY);
        if ($isLrRanker) {
            $lrRanker = new LrNewsRanker($this->di); 
            list($sortedNewsObjDct, $newsFeatureDct) = $lrRanker->ranking(
                $this->channel_id, $this->device_id, $newsObjDct, 
                $prefer, $sample_count);
            if ($strategyTag == "10001_lrRanker") {
                $newsObjDct = $sortedNewsObjDct;
                $newsIdStr = "";
                foreach ($newsObjDct as $newsObj) {
                    $newsIdStr = $newsIdStr . $newsObj->url_sign . ", ";
                }
                $logger->info(sprintf("[di:%s][channel:%s][strategy:%s]" .
                    "[rerank newsId:%s]", $this->device_id, 
                    $this->channel_id, $strategyTag, $newsIdStr));
            }
        }
        
        $ret = array();
        $filter = array();
        foreach ($newsObjDct as $newsId => $newsObj) {
            if (count($ret) >= $sample_count) {
                break;
            }
            if (in_array($newsId, $filter)) {
                $filter[] = $newsId;
            }
            $filter[] = $newsId;
            $ret[] = $newsObj;
        }
        // post filter after ranking
        $simhashFilter = new SimhashFilter($this->di);
        $ret = $simhashFilter->filtering($this->channel_id,
            $this->device_id, $ret);

        /*
        if ($prefer == 'later') {
            $cache = $this->di->get('cache');
            if ($cache->exists("BS_BANNER_SWITCH"))
                $this->InsertBanner($ret);
        }
        //*/
        $this->insertTopic($ret);
        $this->InsertInterests($ret);
        $this->insertAd($ret);
        $this->InsertVideo($prefer, $ret);
        $this->getPolicy()->setDeviceSent($this->device_id, $filter);
        return array($ret, $newsFeatureDct);
    }

    protected function InsertInterests(&$ret) {
        $this->interveneAt($ret, new InterestsIntervene(
            array(
                "device_id" => $this->device_id,
                "os" => $this->os,
                "client_version" => $this->client_version,
                )
            ), 4);
    }

    protected function InsertTopic(&$ret) {
        $this->interveneAt($ret, new TopicIntervene(
            array(
                "device_id" => $this->device_id,
                "net" => $this->net,
                "screen_w" => $this->screen_w,
                "screen_h" => $this->screen_h,
                "os" => $this->os,
                "client_version" => $this->client_version,
                )),
            0);
    }

    protected function InsertBanner(&$ret) {
        $this->interveneAt($ret, 
            new BannerIntervene(array(
                "device_id" => $this->device_id,
                "operating_id" => OPERATING_CHRISTMAS,
                "news_id" => BANNER_NEWS_ID,
                "client_version" => $this->client_version,
                "os" => $this->os,
                "net" => $this->net,
            )
        ), 0);
    }

    protected function InsertVideo($prefer, &$ret) {
        $popularPolicy = new PopularListPolicy($this->di); 
        $options = array();
        if ($prefer == "later") {
            $options["long_tail_weight"] = 0;
        }
        if (Features::Enabled(Features::VIDEO_SUPPORT_FEATURE, 
                $this->client_version, $this->os)) {
            $videoIdLst = $popularPolicy->sampling("30001", $this->device_id,
                        $this->user_id, 1, 3, $prefer, $options);
            $videoIdObjDct = News::BatchGet($videoIdLst);
            if (empty($videoIdObjDct)) {
                return ;
            }
            $videoObjLst = array_values($videoIdObjDct);
            array_splice($ret, 3, 0, $videoObjLst);
            $device_id = $this->device_id;
            $bf_service = $this->di->get("bloomfilter");
            $bf_service->add(BloomFilterService::FILTER_FOR_VIDEO,
                             array_map(
                                       function($key) use ($device_id){
                                           return $device_id . "_" . $key;
                                       }, $videoIdLst));
        }
    }
}
