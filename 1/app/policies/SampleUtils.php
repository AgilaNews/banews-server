<?php
class SampleUtils {
    public static function samplingWithoutReplace($datas, $weights, $required) {
        $sel_indices = array();
        $ret = array();

        assert(count($datas) == count($weights));
        while (true) {
            if (count($datas) <= $required) {
                $ret = array_merge($ret, $datas);
                return $ret;
            }

            if ($required <= count($sel_indices)) {
                $sel_indices = array_slice($sel_indices, 0, $required);
                break;
            }

            $sel = self::rand_uniform($required - count($ret));
            $cdf = self::get_normal_cdf($weights);
            $indices = self::cdf_search_sorted($sel, $cdf);

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

    protected static function rand_uniform($count) {
        $ret = array();
        for ($i = 0; $i < $count; $i++) {
            $ret []= mt_rand() / mt_getrandmax();
        }

        return $ret;
    }

    protected static function get_normal_cdf($weights) {
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

    protected static function cdf_search_sorted($selector, $cdf) {
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

}
