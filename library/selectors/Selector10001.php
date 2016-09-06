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
define("LATELY_NEWS_COUNT", 2);
define('RECOMMEND_NEWS_COUNT',2);

class Selector10001 extends BaseNewsSelector{

    protected function getDeviceGroup($deviceId) { 
        $hashCode = hash('md5', $deviceId);
        $lastChar = substr($hashCode, -1);
        if (in_array($lastChar, array('0', '1', '2', '3', '4', '5', '6', '7'))) {
            return 0;
        } else {
            return 1;
        }
    }

    public function getPolicyTag(){
        return 'popularRanking';
    }

    public function sampling($sample_count, $prefer) {
        $randomPolicy = new ExpDecayListPolicy($this->_di); 
        $popularPolicy = new PopularListPolicy($this->_di); 
        $popularRecommendPolicy = new PopularRecommendPolicy($this->_di);
        $options = array();
        if ($prefer == "later") {
            $options["long_tail_weight"] = 0;
        }
#        $popularNewsCnt = max($sample_count - LATELY_NEWS_COUNT - RECOMMEND_NEWS_COUNT, 1);
#        $popularNewsLst = $popularPolicy->sampling($this->_channel_id, $this->_device_id,
#                $this->_user_id, $popularNewsCnt, 3, $prefer, $options);
        $recommendNewsLst = $popularRecommendPolicy->sampling($this->_channel_id, $this->_device_id,
                $this->_user_id, RECOMMEND_NEWS_COUNT, 3, $prefer, $options);

#        foreach($recommendNewsLst as $recNews) {
#            if(in_array($recNews, $popularNewsLst)) {
#                continue;
#            }
#            $popularNewsLst[] = $recNews;
#        }
#
#        $randomNewsLst = $randomPolicy->sampling($this->_channel_id, $this->_device_id,
#                $this->_user_id, MAX_NEWS_COUNT, 3, $prefer, $options);
#
#        foreach($randomNewsLst as $randomNews) {
#            if (count($popularNewsCnt) >= $sample_count) {
#                break;
#            }
#            if (in_array($randomNews, $popularNewsLst)) {
#                continue;
#            }
#            $popularNewsLst[] = $randomNews;
#        }
        return $recommendNewsLst;
    }

    public function select($prefer) {
        $required = mt_rand(MIN_NEWS_COUNT, MAX_NEWS_COUNT);
        $selected_news_list = $this->sampling($required, $prefer); 
        $models = News::BatchGet($selected_news_list);
        $models = $this->removeInvisible($models);
        $models = $this->removeDup($models);
        if (count($models) > $required) {
            $models = array_slice($models, 0, $required);
        }
        
        $this->getPolicy()->setDeviceSent($this->_device_id, array_keys($models));
        return $models;
    }
}
