<?php
/**
 * @file   NbaIntervene.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Mon Oct 31 18:45:17 2016
 * 
 * @brief  
 * 
 * 
 */
class NbaIntervene extends BaseIntervene {
    public function __construct($context = array()) {
        parent::__construct($context);
        $this->flagSign = "NBA_INTERVENE";
    }
    public function render(){
        return array(array("data" =>
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
                           "tpl" => RenderLib::NEWS_LIST_TPL_NBA,
                           "fix_top" => 1,
                           ));
    }
}
