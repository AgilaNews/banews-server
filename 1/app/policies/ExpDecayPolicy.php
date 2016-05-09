<?php

define ('HIGH_PRIO_HOUR', 48);
define ('DECAY_TOP', 10000);
define ('MIN_WEIGHT', 1);
define ('DECAY_SLOP_PREFER_LATER', 10);
define ('DECAY_SLOP_PREFER_OLDER', 2);

class ExpDecayPolicy extends BasePolicy {
    public function __construct($di) {
        parent::__construct($di);
    }

    public function sampling($channel_id, $device_id, $user_id, $pn = 10, $prefer='later', array $options = null) {
        $valid_news_list = $this->getAllUnsent($channel_id, $device_id);
        $weights = $this->generate_news_weights($valid_news_list, $prefer);

        $ret = $this->samplingWithoutReplace($valid_news_list, $weights, $pn);

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

    protected function rand_uniform($count) {
        $ret = array();
        for ($i = 0; $i < $count; $i++) {
            $ret []= mt_rand() / mt_getrandmax();
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

    protected function get_normal_cdf($weights) {
        if (count($weights) == 0) return;

        $ret = array();
        $ret[0] = $weights[0];

        for ($i = 1; $i < count($weights); $i++) {
            $ret[$i] = $ret[$i-1] + $weights[$i];
        }
        $m = $ret[count($weights) - 1];
        for ($i = 0; $i < count($weights); $i++) {
            $ret[$i] /= $m;
        }

        return $ret;
    }

    protected function cdf_search_sorted($selector, $cdf) {
        $ret = array();

        for ($i = 0; $i < count($selector); $i++) {
            for ($j = 0; $j < count($cdf); $j++) {
                if ($selector[$i] <= $cdf[$j]) {
                    break;
                }
            }

            $ret []= $j;
        }
        return array_unique($ret);
    }

    public function samplingWithoutReplace($datas, $weights, $required) {
        $sel_indices = array();
        $ret = array();

        assert(count($datas) == count($weights));
        while (true) {
            if (count($datas) <= $required) {
                $ret = array_merge($ret, $datas);
                return $ret;
            }

            if ($required <= count($sel_indices)) {
                // got enough
                break;
            }

            $sel = $this->rand_uniform($required - count($ret));
            $cdf = $this->get_normal_cdf($weights);
            $indices = $this->cdf_search_sorted($sel, $cdf);

            foreach ($indices as $index) {
                $sel_indices []= $index;
                $weights[$index] = 0;
            }
        }

        sort($sel_indices);
        foreach ($sel_indices as $sel_index) {
            $ret []= $datas[$sel_index];
        }

        return $ret;
    }
}
