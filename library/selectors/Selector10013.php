<?php
class Selector10013 extends Selector10004 {
    public function select($prefer) {
        $ret = parent::select($prefer);

        if ($prefer == "later") {
            $ret[0] = INTERVENE_TPL_CELL_PREFIX . NEWS_LIST_TPL_NBA;
        }

        return $ret;
    }
}
