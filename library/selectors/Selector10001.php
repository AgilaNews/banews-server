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
    protected function getDeviceGroup($deviceId) { 
        $hashCode = hash('md5', $deviceId);
        $lastChar = substr($hashCode, -1);
        if (in_array($lastChar, 
                array('0', '1', '2', '3', '4', '5', '6', '7'))) {
            return 0;
        } else {
            return 1;
        }
    }

    public function getPolicyTag(){
        $groupId = $this->getDeviceGroup($this->_device_id);
        if ($groupId == 0) {
            return "popularRanking";
        } else {
            return 'personalTopicRec';
        }
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
        $groupId = $this->getDeviceGroup($this->_device_id);
        $popularPolicy = new PopularListPolicy($this->_di); 
        $recNewsLst = array();
        if ($groupId == 0) {
            $recNewsLst = $popularPolicy->sampling($this->_channel_id, 
                $this->_device_id, $this->_user_id, $sample_count, 
                3, $prefer, $options);
        } else {
            $personalTopicPolicy = new PersonalTopicInterestPolicy($this->_di);
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

        if (version_compare($this->_client_version, AD_FEATURE, ">=") && count($ret) >= AD_INTERVENE_POS) {
            $ad_intervene = new AdIntervene(array(
                                                  "type" => NEWS_LIST_TPL_AD_FB_MEDIUM,
                                                  "device" => $this->_device_id,
                                                  ));
            $this->interveneAt($ret, $ad_intervene, AD_INTERVENE_POS);
        }

        $this->getPolicy()->setDeviceSent($this->_device_id, $filter);
        return $ret;
    }
}
