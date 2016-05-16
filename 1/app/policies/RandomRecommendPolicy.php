<?php

define ('RECOMMEND_BASE', 100);

class RandomRecommendPolicy extends BaseRecommendPolicy {
    public function sampling($channel_id, $device_id, $user_id, $pn = 3, array $options = null) {
        $news = $this->redis->getNewsOfchannel($channel_id);
        $news = array_slice($news, 0, RECOMMEND_BASE);
        $weights = array_fill(0, count($news), 1.0); // TODO use same weight sampling firstly
        $ret = SampleUtils::samplingWithoutReplace($news, $weights, $pn);

        return array_map (
            function ($key) use ($ret) {
                return json_decode($key, true)["id"];
            },
            $ret
        );
    }
}
