<?php
define ('HIGH_PRIO_HOUR', 72);
define ('DEFAULT_DAY_TILL_NOW', 7);
define ('DECAY_TOP', 10000);
define ('MIN_WEIGHT', 1);
define ('DECAY_SLOP_PREFER_LATER', 10);
define ('DECAY_SLOP_PREFER_OLDER', 2);

class ExpDecayListPolicy extends BaseListPolicy {
    public function __construct($di) {
        parent::__construct($di);
    }

    public function sampling($channel_id, $device_id, $user_id, $pn, $day_till_now, $prefer, 
                             array $options = array()) {
        $high_prio_hour = HIGH_PRIO_HOUR;
        if ($prefer == 'later') {
            $slop = DECAY_SLOP_PREFER_LATER;
        } else if ($prefer == 'older') {
            $slop = DECAY_SLOP_PREFER_OLDER;
        } else {
            assert(false);
        }
        $decay_top = DECAY_TOP;
        $long_tail_weight = MIN_WEIGHT;
 
        if (isset($options["high_prio_hour"])) {
            $high_prio_hour = $options["high_prio_hour"];
        }
        assert($day_till_now * 24 >= $high_prio_hour);
        if (isset($options["slop"])) {
            $slop = $options["slop"];
        }
        if (isset($options["decay_top"])) {
            $decay_top = $options["decay_top"];
        }
        if (isset($options["long_tail_weight"])) {
            $long_tail_weight = $options["long_tail_weight"];
        }
       
        $valid_news_list = $this->getAllUnsent($channel_id, $device_id, $day_till_now);
        $weights = $this->generate_news_weights($valid_news_list, $prefer, $high_prio_hour, $slop, $decay_top, $long_tail_weight);

        $ret = SampleUtils::SamplingWithoutReplace($valid_news_list, $weights, $pn);

        return array_map (
            function ($key) use ($ret) {
                return $key["id"];
            },
            $ret
        );
    }

    protected function generate_news_weights($valid_news_list, $prefer, $high_prio_hour, $slop, $decay_top, $long_tail_weight){
        $end = time();
        $begin = $end - $high_prio_hour * 3600;
        $decay_weights = $this->generateHalfLifeDecay($decay_top, $slop, $high_prio_hour * 24);
	    $ret = array();

        foreach ($valid_news_list as $news) {
            $time = $news["ptime"];
            if ($time > $begin) {
                $slot = $high_prio_hour - (int)(($time - $begin) / 3600);
                $ret []= $decay_weights[$slot]; 
            } else {
                $ret []= $long_tail_weight;
            }
	    }

        return $ret;
    }

    protected function generateHalfLifeDecay($nz, $slop, $period) {
        $ret = array();

        for ($i = 0; $i < $period; $i++) {
            $ret[$i] = $nz * exp(-$i * log($slop) / 48);
        }

        return $ret;
    }
}
