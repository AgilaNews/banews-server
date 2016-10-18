<?php
class Selector10013 extends BaseNewsSelector {
    public function select($prefer) {
        $ret = parent::select($prefer);

        if ($prefer == "later") {
            $ret[INTERVENE_TPL_CELL_PREFIX . NEWS_LIST_TPL_NBA] = 0;
        }

        return $ret;
    }
}
