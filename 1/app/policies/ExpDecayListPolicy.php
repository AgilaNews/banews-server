<?php

define ('HIGH_PRIO_HOUR', 48);
define ('DECAY_TOP', 10000);
define ('MIN_WEIGHT', 1);
define ('DECAY_SLOP_PREFER_LATER', 10);
define ('DECAY_SLOP_PREFER_OLDER', 2);

class ExpDecayListPolicy extends BaseListPolicy {
    public function __construct($di) {
        parent::__construct($di);
    }

    public function sampling($channel_id, $device_id, $user_id, $pn = 10, $prefer='later', array $options = null) {
        $valid_news_list = $this->getAllUnsent($channel_id, $device_id);
        $weights = $this->generate_news_weights($valid_news_list, $prefer);

        $ret = SampleUtils::SamplingWithoutReplace($valid_news_list, $weights, $pn);

        return array_map (
            function ($key) use ($ret) {
                return $key["id"];
            },
            $ret
        );
    }

    protected function generate_news_weights($valid_news_list, $prefer){
        $end = time();
        $begin = $end - HIGH_PRIO_HOUR * 3600;
        if ($prefer == 'later') {
            $slop = DECAY_SLOP_PREFER_LATER;
        } else if ($prefer == 'older') {
            $slop = DECAY_SLOP_PREFER_OLDER;
        } else {
            assert(false);
        }

        $decay_weights = $this->generateHalfLifeDecay(DECAY_TOP, $slop, HIGH_PRIO_HOUR * 24);
	    $ret = array();

        foreach ($valid_news_list as $news) {
            $time = $news["ptime"];
            if ($time > $begin) {
                $slot = HIGH_PRIO_HOUR - (int)(($time - $begin) / 3600);
                $ret []= $decay_weights[$slot]; 
            } else {
                $ret []= MIN_WEIGHT;
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
