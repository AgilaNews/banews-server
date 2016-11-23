<?php
define('NBA_LATELY_NEWS_COUNT', 6);

class Selector10013 extends Selector10004 {
    public function getLatelyNewsCount(){
        return NBA_LATELY_NEWS_COUNT;
    }

    public function select($prefer) {
        $ret = parent::select($prefer);

        if ($prefer == "later") {
            if (version_compare($this->_client_version, FIXTOP_NBA_FEATURE, ">=") && $this->_os == "android") {
                $intervene = new NbaIntervene(array());
                $this->interveneAt($ret, $intervene, 0);
            }
        }

        return $ret;
    }
}
