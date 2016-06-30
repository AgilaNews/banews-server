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
define('MIN_NEWS_SEND_COUNT', 8);
define('MAX_NEWS_SENT_COUNT', 12);
define('MORE_NEWS_FACTOR', 1.5);
class BaseNewsSelector {
    public function __construct($channel_id, $di) {
        $this->_channel_id = $channel_id;
        $this->_di = $di;
    }

    protected function getPolicy(){
        return new ExpDecayListPolicy($this->_di);
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
            if (array_key_exists($news_model->content_sign, $uniq) &&
                $uniq[$news_model->content_sign]->source_name == $news_model->source_name
            ) {
                //content sign dup and same source, continue
                continue;
            }

            $ret [$sign] = $news_model;
            $uniq[$news_model->content_sign] = $news_model;
        }

        return $ret;
    }

    public function select($device_id, $user_id, $prefer) {
        //we use exp decay method as default select method
        $policy = $this->getPolicy();

        /*
            get random number of news we want
            because we may get duplicated news because of content similariy, 
            so we query more news than we requried, then multiple the random number with a factor
            'MORE_NEWS_FACTOR'
        */
        $required = mt_rand(MIN_NEWS_SEND_COUNT, MAX_NEWS_SENT_COUNT);
        #I don't known if 1.5 is enough
        $base = round(MAX_NEWS_SENT_COUNT * MORE_NEWS_FACTOR);

        $selected_news_list = $policy->sampling($this->_channel_id, $device_id, 
                                                null, $base, $prefer);
        $models = News::BatchGet($selected_news_list);
        $models = $this->removeInvisible($models);
        $models = $this->removeDup($models);
        if (count($models) > $required) {
            $models = array_slice($models, 0, $required);
        }
        
        $policy->setDeviceSent($device_id, array_keys($models));
        return $models;
    }
}
