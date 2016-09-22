<?php
class Render10013 extends BaseListRender {
    public function __construct($controller){ 
        parent::__construct($controller);
    }

    public function render($models) {
        $top = array();
        
        if ($models[0] == INTERVENE_TPL_CELL_PREFIX . NEWS_LIST_TPL_NBA) {
            if (version_compare($this->_client_version, FIXTOP_NBA_FEATURE, ">=")) {
                $top = array(array("data" =>
                             array(
                                   array(
                                         "title" => "Schedule",
                                         "logo" => IMAGE_PREFIX . "/nba/nba_schedule.png",
                                         "digest" => "",
                                         "page" => "/nba_schedule.html",
                                         ),
                                   array(
                                         "title" => "Standing",
                                         "logo" => IMAGE_PREFIX . "/nba/nba_standing.png",
                                         "digest" => "",
                                         "page" => "/nba_standings.html",
                                         ),
                                   ),
                             "tpl" => NEWS_LIST_TPL_NBA,
                             "fix_top" => 1,
                                   ));

            }
            $models = array_slice($models, 1);
        }
        
        $ret = parent::render($models);
        return array_merge($top, $ret);
    }
}
