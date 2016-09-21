<?php
class Selector10013 extends BaseNewsSelector {
    public function select($prefer) {
        $ret = parent::select($prefer);

        if ($prefer == "later") {
            $ret = array_merge(
                               array(INTERVENE_TPL_CELL_PREFIX . NEWS_LIST_TPL_NBA),
                               $ret
                               );
        }

        return $ret;
    }
}
