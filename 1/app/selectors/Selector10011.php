<?php
define("MIN_IMG_SENT_COUNT", 10);
define("MAX_IMG_SEND_COUNT", 12);
class Selector10011 extends BaseNewsSelector {
    public function getPolicyTag() {
        return "random"; 
    }

    public function getPolicy() {
        return new RandomListPolicy($this->_di); 
    }
    public function sampling($sampling_count, $prefer) {
        $policy = $this->getPolicy();
        return $policy->sampling($this->_channel_id, $this->_device_id,
                                 $this->_user_id, $sampling_count, $prefer);
    }

    public function select($prefer) {
        $required = mt_rand(MIN_IMG_SENT_COUNT, MAX_IMG_SEND_COUNT);
        $selected_news_list = $this->sampling_count($required, $prefer);
        $models = News::batchGet($selected_news_list);
        $models = $this->removeInvisible($models);
        
        $policy->setDeviceSent($device_id, array_keys($models));
        return $models;
    }
}
