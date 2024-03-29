<?php

define ('RECOMMEND_BASE', 100);

class RandomRecommendPolicy extends BaseRecommendPolicy {
    public function sampling($channel_id, $device_id, $user_id, $myself, 
        $pn = 3, $day_till_now = 7, array $options = null) {
        $news = $this->redis->getNewsOfChannel($channel_id, $day_till_now);
        $news = array_slice($news, 0, RECOMMEND_BASE);
        $weights = array_fill(0, count($news), 1.0); // TODO use same weight sampling firstly
        $ret = SampleUtils::samplingWithoutReplace($news, $weights, $pn);

        return array_map (
            function ($key) use ($ret) {
                return $key["id"];
            },
            $ret
        );
    }
}
