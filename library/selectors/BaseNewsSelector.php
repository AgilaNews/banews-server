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
use Phalcon\DI;
define('MIN_NEWS_SEND_COUNT', 6);
define('MAX_NEWS_SENT_COUNT', 8);
define('MORE_NEWS_FACTOR', 1.5);
define("DEFAULT_SAMPLING_DAY", 7);

class BaseNewsSelector {
    public function __construct($channel_id, $controller) {
        $this->_channel_id = $channel_id;
        $this->_device_id = $controller->deviceId;
        $this->_user_id = $controller->userSign;
        $this->_client_version = $controller->client_version;
        $this->_di = $controller->di;
    }

    protected function sampling($sample_count, $prefer){
        return $this->getPolicy()->sampling($this->_channel_id, $this->_device_id, $this->_user_id,
                                            $sample_count, DEFAULT_SAMPLING_DAY, $prefer);
    }


    public function getPolicy() {
        if (!isset($this->_policy)) {
            $this->_policy = new ExpDecayListPolicy($this->_di); 
        }
        return $this->_policy;
    }

    
    public function getPolicyTag(){
        return "expdecay";
    }


    protected function removeInvisible($models) {
        $ret = array();

        foreach ($models as $sign => $news_model) {
            if ($news_model && $news_model->is_visible == 1) {
                $ret[$sign] = $news_model;
            }
        }

        return $ret;
    }

    protected function removeDup($models) {
        $ret = array();
        $uniq = array();

        foreach ($models as $sign => $news_model) { 
            if (array_key_exists($news_model->content_sign, $uniq) 
            ) {
                //content sign dup continue
                continue;
            }

            $ret [$sign] = $news_model;
            $uniq[$news_model->content_sign] = $news_model;
        }

        return $ret;
    }

    public function select($prefer) {
        /*
            get random number of news we want
            because we may get duplicated news because of content similariy, 
            so we query more news than we requried, then multiple the random number with a factor
            'MORE_NEWS_FACTOR'
        */
        $required = mt_rand(MIN_NEWS_SEND_COUNT, MAX_NEWS_SENT_COUNT);
        //I don't known if 1.5 is enough
        $base = round(MAX_NEWS_SENT_COUNT * MORE_NEWS_FACTOR);

        $selected_news_list = $this->sampling($base, $prefer); 
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

    protected function interveneAt(&$ret, $intervene, $pos) {
        array_splice($ret, $pos, 0, array($intervene));
    }

    protected function insertAd(&$ret) {
        if (version_compare($this->_client_version, AD_FEATURE, ">=") && count($ret) >= AD_INTERVENE_POS) {
            $abservice = DI::getDefault()->get('abtest');
            $t = $abservice->getTag("timeline_ad_position");
            $device_md5 = md5($this->_device_id);

            $ad_intervene = new AdIntervene(array(
                                                  "type" => NEWS_LIST_TPL_AD_FB_MEDIUM,
                                                  "device" => $this->_device_id,
                                                  ));

            if ($t == "forth_pos") {
                $pos = 3;
            else if ($t == "six_pos") {
                $pos = 5 
            } else {
                $pos = AD_INTERVENE_POS;
            }
            if (count($ret) >= $pos) {
                $this->interveneAt($ret, $ad_intervene, $pos);
            }
        }
    }
}
