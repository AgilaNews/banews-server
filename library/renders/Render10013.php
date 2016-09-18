<?php
class Render10013 extends BaseListRender {
    public function __construct($controller){ 
        parent::__construct($controller);
    }

    public function render($models) {
        //TODO change this to mysql
        $ret = parent::render($models);
        $top = array("data" =>
                     array(
                           array(
                                 "title" => "Schedule",
                                 "logo" => IMAGE_PREFIX . "/nba_schedule.png",
                                 "digest" => "heheda",
                                 ),
                           array(
                                 "title" => "Standing",
                                 "logo" => IMAGE_PREFIX . "/nba_standing.png",
                                 "digest" => "memeda",
                                 ),
                           ),
                     "tpl" => NEWS_LIST_TPL_NBA,
                     );
        
        $ret = array_merge(array($top), $ret);
        return $ret;
    }
}
