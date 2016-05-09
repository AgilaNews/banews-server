<?php
class RandomPolicy extends BasePolicy {
    public function __construct($di) {
        parent::__construct($di);
    }

    public function sampling($channel_id, $device_id, $user_id, $pn = 10, $prefer = 'later', array $options = null) {
        $valid_news_list = $this->getAllUnsent($channel_id, $device_id);

        $ret = array_map(
            function($key) use ($valid_news_list) {
                return $valid_news_list[$key]["id"];
                },
                array_rand($valid_news_list, $pn)
            );

        return $ret;
    }  
}
