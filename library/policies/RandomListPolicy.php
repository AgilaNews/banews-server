<?php
class RandomListPolicy extends BaseListPolicy {
    public function __construct($di) {
        parent::__construct($di);
    }

    public function sampling($channel_id, $device_id, $user_id, $pn, 
                             $day_till_now, $prefer, array $options = array()) {
        $valid_news_list = $this->getAllUnsent($channel_id, $device_id, null);

        if (!$valid_news_list) {
            return array();
        }

        $ret = array_map(
            function($key) use ($valid_news_list) {
                return $valid_news_list[$key]["id"];
                },
                array_rand($valid_news_list, $pn)
            );

        return $ret;
    }  
}
