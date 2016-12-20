<?php
define ('HIGH_PRIO_HOUR', 72);
define ('DEFAULT_DAY_TILL_NOW', 7);
define ('DECAY_TOP', 10000);
define ('MIN_WEIGHT', 1);
define ('DECAY_SLOP_PREFER_LATER', 10);
define ('DECAY_SLOP_PREFER_OLDER', 2);

class VideoExpDecayListPolicy extends BaseListPolicy {
    public function __construct($di) {
        parent::__construct($di);
    }

    public function sampling($channel_id, $device_id, $user_id, $pn, $day_till_now, $prefer, 
                             array $options = array()) {  
        $valid_news_list = $this->getAllUnsent($channel_id, $device_id, $day_till_now);
        $weights = $this->generate_news_weights($valid_news_list);

        $ret = SampleUtils::SamplingWithoutReplace($valid_news_list, $weights, $pn);

        return array_map (
            function ($key) use ($ret) {
                return $key["id"];
            },
            $ret
        );
    }

    protected function generate_news_weights($valid_news_list){
	    $ret = array();

        foreach ($valid_news_list as $news) {
            $ret []= $news["weight"];
	    }

        return $ret;
    }
}
