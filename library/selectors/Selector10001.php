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
define('RECOMMEND_NEWS_COUNT',3);

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
        $groupId = $this->getDeviceGroup($this->_device_id);
        if ($groupId == 0) {
            return "expdecay";
        } else {
            return 'popularRecommend';
        }
    }

    public function sampling($sample_count, $prefer) {
        $randomPolicy = new ExpDecayListPolicy($this->_di); 
        $popularPolicy = new PopularListPolicy($this->_di); 
        $popularRecommendPolicy = new PopularRecommendPolicy($this->_di);
        $options = array();
        if ($prefer == "later") {
            $options["long_tail_weight"] = 0;
        }
        $groupId = $this->getDeviceGroup($this->_device_id);
        $randomPolicy = new ExpDecayListPolicy($this->_di);
        if ($groupId == 0) {
            return $randomPolicy->sampling($this->_channel_id, $this->_device_id, 
                $this->_user_id, $sample_count, 3, $prefer, $options);
        } else {
            $retNewsLst = array();
            $popularNewsCnt = max($sample_count, 1);
            $popularNewsLst = $popularPolicy->sampling($this->_channel_id, $this->_device_id,
                    $this->_user_id, $popularNewsCnt, 3, $prefer, $options);
            $recommendNewsLst = $popularRecommendPolicy->sampling($this->_channel_id, $this->_device_id,
                    $this->_user_id, RECOMMEND_NEWS_COUNT, 4, $prefer, $options);
    
            $retNewsLst[] = $popularNewsLst[0];
            foreach($recommendNewsLst as $recNews) {
                if(in_array($recNews, $retNewsLst)) continue;
                $retNewsLst[] = $recNews;
            }
            for($x=1;$x<count($popularNewsLst);$x++) {
                if (count($retNewsLst) >= $sample_count){
                    break;
                }
                if (in_array($popularNewsLst[x], $retNewsLst)){
                    continue;
                }
                $retNewsLst[] = $popularNewsLst[x];
            }
    
            $randomNewsLst = $randomPolicy->sampling($this->_channel_id, $this->_device_id,
                    $this->_user_id, MAX_NEWS_COUNT, 3, $prefer, $options);
    
            foreach($randomNewsLst as $randomNews) {
                if (count($retNewsLst) >= $sample_count) {
                    break;
                }
                if (in_array($randomNews, $retNewsLst)) {
                    continue;
                }
                $retNewsLst[] = $randomNews;
            }
            return $retNewsLst;
        }
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
