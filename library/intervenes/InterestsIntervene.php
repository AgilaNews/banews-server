<?php
/**
 * 
 * @file    BannerIntervene.php
 * @authors Zhao Yulong (elysium.zyl@gmail.com)
 * @date    2016-12-04 13:07:07
 * @version $Id$
 */

use Phalcon\DI;

define("INTERESTS_INTERVENE_KEY", "INTERESTS_INTERVENE_");
define("INTERESTS_INTERVENE_TTL", 86400);

class InterestsIntervene extends BaseIntervene {
    public function __construct($context = array()) {
        parent::__construct($context);
        if ($this->isDeviceUsed($context["device_id"])) {
            $this->empty = true;
        } else if ($this->tryBloomfilter($context["device_id"])) {
            $this->empty = true;
        }
    }

    public function render() {
        if (!Features::Enabled(Features::INTERESTS_FEATURE, 
            $this->context["client_version"], 
            $this->context["os"])) {
            return null;
        }
        $device_id = $this->context["device_id"];

        if ($this->tryBloomfilter($device_id)) {
            return null;
        }

        if ($this->isDeviceUsed($device_id)) {
            return null;
        }

        $this->setDeviceUsed($device_id);
        return array("tpl" => NEWS_LIST_INTERESTS);
    }

    protected function setDeviceUsed($device_id) {
        $cache = DI::getDefault()->get('cache');
        if (!$cache) {
            return;
        }
    
        $key = INTERESTS_INTERVENE_KEY . $device_id;
        $cache->multi();
        $cache->set($key, 1);
        $cache->expire($key, INTERESTS_INTERVENE_TTL);
        $cache->exec();
    }

    protected function isDeviceUsed($device_id) {
        $cache = DI::getDefault()->get('cache');
        if (!$cache) {
            return true;
        }

        $key = INTERESTS_INTERVENE_KEY . $device_id;
        return $cache->exists($key);
    }

    protected function tryBloomfilter($device_id) {
        $filterName = BloomFilterService::FILTER_FOR_INTERESTS;
           
        $bf_service = DI::getDefault()->get("bloomfilter");
        $ret = $bf_service->test(
            $filterName,
            $device_id
            );
        
        return $ret;
    }
}
