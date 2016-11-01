<?php
class Render10013 extends BaseListRender {
    public function __construct($controller){
        parent::__construct($controller);
    }

    public function render($models) {
        $intervenes = array();
        $normal = array();
        
        foreach ($models as $idx => $model) {
            if ($this->isIntervened($model)) {
                if (version_compare($this->_client_version, FIXTOP_NBA_FEATURE, ">=")) {
                    $intervenes[$idx] = $model->render();                  
                }
            } else {
                $normal []= $model;
            }
        }

        $ret = parent::render($normal);

        foreach ($intervenes as $idx => $intervene) {
            array_splice($ret, $idx, 0, $intervene);
        }
        
        return $ret;
    }
}
