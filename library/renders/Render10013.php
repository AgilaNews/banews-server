<?php
class Render10013 extends BaseListRender {
    public function __construct($controller){
        parent::__construct($controller);
    }

    public function render($models) {
        $top = array();

        foreach ($models as $idx => $model) {
            if ($this->isIntervened($model, NEWS_LIST_TPL_NBA)) {
                if (version_compare($this->_client_version, FIXTOP_NBA_FEATURE, ">=")) {
                    $top = array(array("data" =>
                                       array(
                                             array(
                                                   "title" => "Schedule",
                                                   "logo" => IMAGE_PREFIX . "/nba_schedule.png",
                                                   "digest" => "",
                                                   "page" => "/nba/nba_schedule.html",
                                                   ),
                                             array(
                                                   "title" => "Standings",
                                                   "logo" => IMAGE_PREFIX . "/nba_standing.png",
                                                   "digest" => "",
                                                   "page" => "/nba/nba_standings.html",
                                                   ),
                                             ),
                                       "tpl" => NEWS_LIST_TPL_NBA,
                                       "fix_top" => 1,
                                       ));
                    
                }
            }
            
            array_splice($models, $idx, $idx);
            break;
        }

        $ret = parent::render($models);
        return array_merge($top, $ret);
    }
}
