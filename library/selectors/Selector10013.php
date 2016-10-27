<?php
define('NBA_LATELY_NEWS_COUNT', 6);

class Selector10013 extends Selector10004 {
    public function getLatelyNewsCount(){
        return NBA_LATELY_NEWS_COUNT;
    }

    public function select($prefer) {
        $ret = parent::select($prefer);

        if ($prefer == "later") {
            $ret[INTERVENE_TPL_CELL_PREFIX . NEWS_LIST_TPL_NBA] = 0;
        }

        return $ret;
    }
}
