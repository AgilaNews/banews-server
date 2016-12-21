<?php
/**
 * 
 * @file    BannerIntervene.php
 * @authors Zhao Yulong (elysium.zyl@gmail.com)
 * @date    2016-12-04 13:07:07
 * @version $Id$
 */

use Phalcon\DI;

class InterestsIntervene extends BaseIntervene {
    public function render() {
        $this->setDeviceUsed($this->context["devideId"]);
        return array("tpl" => NEWS_LIST_INTERESTS);
    }


    protected function setDeviceUsed($device_id) {
    }
}
