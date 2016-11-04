<?php
define('NBA_LATELY_NEWS_COUNT', 6);

class Selector10013 extends Selector10004 {
    public function getLatelyNewsCount(){
        return NBA_LATELY_NEWS_COUNT;
    }

    public function select($prefer) {
        $ret = parent::select($prefer);

        if ($prefer == "later") {
            $intervene = new NbaIntervene(array());
            $this->interveneAt($ret, $intervene, 0);
        }

        return $ret;
    }
}
