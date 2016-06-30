<?php
define("MIN_IMG_SENT_COUNT", 10);
define("MAX_IMG_SEND_COUNT", 12);
class Selector10011 extends BaseNewsSelector {
    public function __construct($channel_id, $di) {
        parent::__construct($channel_id, $di);
    }

    public function getPolicyTag() {
        return "random"; 
    }

    public function getPolicy() {
        return new RandomListPolicy($this->_di); 
    }

    public function select($device_id, $user_id, $prefer) {
        $policy = $this->getPolicy();
        $required = mt_rand(MIN_IMG_SENT_COUNT, MAX_IMG_SEND_COUNT);
        $selected_news_list = $policy->sampling($this->_channel_id, $device_id,
                                                    null, $required, $prefer);
        $models = News::batchGet($selected_news_list);
        $models = $this->removeInvisible($models);
        
        $policy->setDeviceSent($device_id, array_keys($models));
        return $models;
    }
}
