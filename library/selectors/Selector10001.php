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
define ('POPULAR_NEWS_CNT', 2);

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
                $this->_device_id, $this->_user_id, MAX_NEWS_COUNT, 
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
            $candidateNewsLst = array_unique(array_merge($popularNewsLst,
                $topicRecNewsLst)); 
            $lrRanker = new LrNewsRanker($this->_di); 
            $recNewsLst = $lrRanker->ranking($this->_channel_id,
                $this->_device_id, $candidateNewsLst, $prefer, $sample_count);
        }
            
        if (count($recNewsLst) < $sample_count) {
            $recNewsLst = $this->emergence($sample_count, 
                $recNewsLst, $options, $prefer);
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
        
        $this->insertAd($ret);
        $this->getPolicy()->setDeviceSent($this->_device_id, $filter);
        return $ret;
    }
}
