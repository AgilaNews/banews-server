<?php

define ('MAX_SIMILARITY_THRE', 0.9);
define ('SIMHASH_DEFAULT_VALUE', 0.0);

class SimhashFilter extends BaseNewsFilter {

    protected function calHammingDistance($preStr, $latStr) {
        $len = 0;
        for ($idx=0; $idx<strlen($preStr); $idx++) {
            if ($preStr[$idx] != $latStr[$idx]) {
                    $len += 1;
                }
        }
        return $len;
    }

    protected function gaussianDensity($val, $deviation=8) {
        $res = -(1/2) * pow($val/$deviation, 2);
        $res = (1/sqrt(2*pi())) * exp($res);
        return $res;
    } 

    protected function calSimilarity($preStr, $latStr) {
        if (empty($preStr) || empty($latStr)) {
            return SIMHASH_DEFAULT_VALUE;
        }
        if (strlen($preStr) != strlen($latStr)) {
            $this->_logger->warning(sprintf(
                'The simhash values have different sizes (%s bits and %s bits).', 
                strlen($preStr), strlen($latStr)));
            return SIMHASH_DEFAULT_VALUE;
        }
        $len = $this->calHammingDistance($preStr, $latStr);
        $sim = $this->gaussianDensity($len) / 
            $this->gaussianDensity(0);
        return $sim;
    }

    public function filtering($channelId, $deviceId, $newsObjLst, 
        array $options=array()) {
        $newsCnt = count($newsObjLst);
        $filterNewsObjLst = array();
        for ($i=$newsCnt-1; $i>=0; $i--) {
            $isFilter = false;
            for ($j=$i-1; $j>=0; $j--) {
                $preHashStr = $newsObjLst[$i]->related_sign;
                $latHashStr = $newsObjLst[$j]->related_sign;
                $sim = $this->calSimilarity($preHashStr, $latHashStr);
                if ($sim > MAX_SIMILARITY_THRE) {
                    $isFilter = true;
                    break;
                }
            }
            if (!$isFilter) {
                $filterNewsObjLst[] = $newsObjLst[$i];
            }
        }
        if (!empty($filterNewsObjLst)) {
            $filterNewsObjLst = array_reverse($filterNewsObjLst);
        }
        return $filterNewsObjLst;
    }
}
