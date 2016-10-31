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
define('MIN_NEWS_SEND_COUNT', 6);
define('MAX_NEWS_SENT_COUNT', 8);
define('MORE_NEWS_FACTOR', 1.5);
define("DEFAULT_SAMPLING_DAY", 7);

class BaseNewsSelector {
    public function __construct($channel_id, $device_id, $user_id, $di) {
        $this->_channel_id = $channel_id;
        $this->_device_id = $device_id;
        $this->_user_id = $user_id;
        $this->_di = $di;
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
        
        $this->getPolicy()->setDeviceSent($this->_device_id, $filter);
        return $ret;
    }

    protected function interveneAt(&$ret, $tpl, $pos) {
        $key = INTERVENE_TPL_CELL_PREFIX . $tpl;

        array_splice($ret, $pos, 0, $key);
    }
}
