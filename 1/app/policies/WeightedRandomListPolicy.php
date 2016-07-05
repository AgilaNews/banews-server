<?php
class WeightedRandomListPolicy extends BaseListPolicy {
    public function __construct($di) {
        parent::__construct($di);
    }

    public function sampling($channel_id, $device_id, $user_id, $pn, $day_till_now, $prefer, 
                             array $options = array()) {
        $valid_news_list = $this->getAllUnsent($channel_id, $device_id, null);
        $weights = array_map (
            function ($key) use ($valid_news_list) {
                if ($key["weight"] == 0) {
                    return 1;
                } else {
                    return $key["weight"];
                }
            },
            $valid_news_list
        );
        $ret = SampleUtils::SamplingWithoutReplace($valid_news_list, $weights, $pn);

        return array_map(
            function ($key) use ($ret) {
                return $key["id"];
            },
            $ret
        );
    }
}
