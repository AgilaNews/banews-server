<?php
define("MIN_IMG_SENT_COUNT", 10);
define("MAX_IMG_SEND_COUNT", 12);
class Selector10011 extends BaseNewsSelector {
    public function getPolicyTag() {
        return "random"; 
    }

    public function getPolicy() {
        if (!$this->_policy) {
            $this->_policy = new RandomListPolicy($this->_di); 
        }
        return $this->_policy;
    }

    public function sampling($sampling_count, $prefer) {
        return $this->getPolicy()->sampling($this->_channel_id, $this->_device_id,
                                 $this->_user_id, $sampling_count, 30, $prefer);
    }

    public function select($prefer) {
        $required = mt_rand(MIN_IMG_SENT_COUNT, MAX_IMG_SEND_COUNT);
        $selected_news_list = $this->sampling($required, $prefer);
        $models = News::batchGet($selected_news_list);
        $models = $this->removeInvisible($models);
        
        $this->getPolicy()->setDeviceSent($this->_device_id, array_keys($models));
        return $models;
    }
}
