<?php
define("MIN_IMG_SEND_COUNT", 10);
define("MAX_IMG_SEND_COUNT", 12);

class Selector10011 extends BaseNewsSelector {
    public function getPolicyTag() {
        return "origin_web_weight"; 
    }

    public function getPolicy() {
        if (!isset($this->_policy)) {
            $this->_policy = new WeightedRandomListPolicy($this->_di); 
        }
        return $this->_policy;
    }

    public function sampling($sampling_count, $prefer) {
        return $this->getPolicy()->sampling($this->_channel_id, $this->_device_id,
                                 $this->_user_id, $sampling_count, null, $prefer);
    }

    public function select($prefer) {
        $required = mt_rand(MIN_IMG_SEND_COUNT, MAX_IMG_SEND_COUNT);
        $selected_news_list = $this->sampling($required, $prefer);
        $models = News::batchGet($selected_news_list);
        $models = $this->removeInvisible($models);
        
        $this->getPolicy()->setDeviceSent($this->_device_id . "_10011", array_keys($models),
                                          IMG_CHANNEL_CACHE_SENT_MASK_MAX, IMG_CHANNEL_CACHE_SENT_TTL);
        return $models;
    }
}
