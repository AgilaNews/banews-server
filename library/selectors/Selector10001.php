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
define("OPERATING_CHRISTMAS", 1);
define("CHIRISTMAS_NEWS_ID", "WabhTzb6bbs=");
define('BANNER_ID_NEW', 'WabhTzb6bbs=');
define('BANNER_ID_OLD', 'WabhTzb6bbs=');

class Selector10001 extends BaseNewsSelector{

    public function getPolicyTag(){
        $abService = $this->_di->get('abtest');
        $experiment = 'channel_' . $this->_channel_id . '_strategy';
        $tag = $abService->getTag($experiment);
        if ($tag != "10001_popularRanking" and $tag != "10001_personalTopicRec") {
            $tag = "10001_lrRanker";
        }
        return $tag;
    }

    public function emergence($sample_count, $recNewsLst, $options, $prefer) {
        $randomPolicy = new ExpDecayListPolicy($this->_di); 
        $randomNewsLst = $randomPolicy->sampling($this->_channel_id, 
                $this->_device_id, $this->_user_id, $sample_count, 
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
        $popularPolicy = new PopularListPolicy($this->_di); 
        $personalTopicPolicy = new PersonalTopicInterestPolicy($this->_di);
        $recNewsLst = array();
        $logger = $this->_di->get('logger');
        if ($strategyTag == "10001_popularRanking") {
            $recNewsLst = $popularPolicy->sampling($this->_channel_id, 
                $this->_device_id, $this->_user_id, $sample_count, 
                3, $prefer, $options);
        } elseif ($strategyTag == "10001_personalTopicRec") {
            $recNewsLst = $personalTopicPolicy->sampling(
                $this->_channel_id, $this->_device_id, $this->_user_id,
                $sample_count - POPULAR_NEWS_CNT, 3, $prefer, $options);
            if (count($recNewsLst) < $sample_count) {
                $popNewsLst = $popularPolicy->sampling($this->_channel_id, 
                    $this->_device_id, $this->_user_id, $sample_count, 
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
        } else {
            // combine popular & topic recommend recall with rerank
            $popularNewsLst = $popularPolicy->sampling($this->_channel_id, 
                $this->_device_id, $this->_user_id, 50, 3, $prefer, 
                $options);
            if (count($popularNewsLst) == 0) {
                $popularNewsLst = $this->emergence(30, $recNewsLst, 
                    $options, $prefer);
            }
            $lrRanker = new LrNewsRanker($this->_di); 
            $recNewsLst = $lrRanker->ranking($this->_channel_id,
                $this->_device_id, $popularNewsLst, $prefer, $sample_count);
        }
        $logger->info("====>channel 10001 strategy: " . $strategyTag .
            ". deviceId:" . $this->_device_id . ". newsCnt:" 
            . count($recNewsLst));

        if (count($recNewsLst) < $sample_count) {
            $recNewsLst = $this->emergence($sample_count, 
                $recNewsLst, $options, $prefer);
        }

        if (Features::Enabled(Features::VIDEO_SUPPORT_FEATURE, $this->_client_version, $this->_os)) {
            $videos = $popularPolicy->sampling("30001", $this->_device_id,
                        $this->_user_id, 1, 3, $prefer, $options);
            array_splice($recNewsLst, 3, 0, $videos);

            $device_id = $this->_device_id;
            $bf_service = $this->_di->get("bloomfilter");
            $bf_service->add(BloomFilterService::FILTER_FOR_VIDEO,
                             array_map(
                                       function($key) use ($device_id){
                                           return $device_id . "_" . $key;
                                       }, $videos));
        }
        return $recNewsLst;
    }

    public function select($prefer) {
        $required = mt_rand(MIN_NEWS_COUNT, MAX_NEWS_COUNT);
        $selected_news_list = $this->sampling($required, $prefer);
        $models = News::BatchGet($selected_news_list);
        $models = $this->removeInvisible($models);
        $models = $this->removeDup($models);

        $ret = array();
        $filter = array();
        for ($i = 0; $i < count($selected_news_list); $i++) {
            if (array_key_exists($selected_news_list[$i], $models)) {
                $ret []= $models[$selected_news_list[$i]];
                $filter []= $models[$selected_news_list[$i]]->url_sign;
                if (count($ret) >= $required) {
                    break;
                }
            }
        }
        
        /*
        $this->interveneAt($ret, new TempTopIntervene(array(
                                                      "device_id" => $this->_device_id,
                                                      "operating_id" => OPERATING_CHRISTMAS,
                                                      "news_id" => CHRISTMAS_NEWS_ID,
                                                      )), 0);
                                                      */
        $this->InsertBanner($ret);
        $this->insertAd($ret);
        $this->getPolicy()->setDeviceSent($this->_device_id, $filter);
        return $ret;
    }

    protected function InsertBanner(&$ret) {
        $banner_id = BANNER_ID_OLD;
        if (Features::Enabled(Features::BANNER_FEATURE, $this->_client_version, $this->_os)) {
            $banner_id = BANNER_ID_NEW;
        }
        
        $this->interveneAt($ret, new BannerIntervene(array(
                                                      "device_id" => $this->_device_id,
                                                      "operating_id" => OPERATING_CHRISTMAS,
                                                      "news_id" => $banner_id,
                                                      "client_version" => $this->_client_version,
                                                      "os" => $this->_os,
                                                      "net" => $this->_net,
                                                      )), 0);
    }
}
