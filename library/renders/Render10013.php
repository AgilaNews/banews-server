<?php
class Render10013 extends BaseListRender {
    public function __construct($controller){ 
        parent::__construct($controller);
    }

    public function render($models) {
        //TODO change this to mysql
        $ret = parent::render($models);

        if (version_compare($this->_client_version, FIXTOP_NBA_FEATURE, ">=")) {
                $top = array("data" =>
                             array(
                                   array(
                                         "title" => "Schedule",
                                         "logo" => IMAGE_PREFIX . "/nba_schedule.png",
                                         "digest" => "",
                                         ),
                                   array(
                                         "title" => "Standing",
                                         "logo" => IMAGE_PREFIX . "/nba_standing.png",
                                         "digest" => "",
                                         ),
                                   ),
                             "tpl" => NEWS_LIST_TPL_NBA,
                             "fix_top" => 1,
                             );
                $ret = array_merge(array($top), $ret);
            }
        return $ret;
    }
}
