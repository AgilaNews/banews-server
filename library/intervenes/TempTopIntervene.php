<?php
/**
 * @file   TempTopIntervene.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Mon Oct 31 21:40:47 2016
 * 
 * @brief  
 * 
 * 
 */
use Phalcon\DI;

define("TEMP_TOP_INTERVENE_KEY", "BS_TOP_INTERVENE_%s_%s_%s");
define("TEMP_TOP_INTERVENE_TTL", 86400);

class TempTopIntervene extends BaseIntervene {
    public function render(){
        $news_id = $this->context["news_id"];
        $device_id = $this->context["device_id"];
        $operating_id = $this->context["operating_id"];

        if ($this->isDeviceUsed($news_id, $device_id, $operating_id)) {
            return null;
        } else {
            $news = News::getBySign($news_id);
            
            $this->setDeviceUsed($news_id, $device_id, $operating_id);
            return $news;
        }
    }

    protected function setDeviceUsed($news_id, $device_id, $operating_id) {
        $cache = DI::getDefault()->get('cache');
        if (!$cache) {
            return;
        }
    
        $key = sprintf(TEMP_TOP_INTERVENE_KEY, $news_id, $device_id, $operating_id);
        $cache->multi();
        $cache->set($key, 1);
        $cache->expire($key, TEMP_TOP_INTERVENE_TTL);
        $cache->exec();
    }

    protected function isDeviceUsed($news_id, $device_id, $operating_id){
        $cache = DI::getDefault()->get('cache');
        if (!$cache) {
            return true;
        }
        
        $key = sprintf(TEMP_TOP_INTERVENE_KEY, $news_id, $device_id, $operating_id);
        $exists = $cache->exists($key);
        return $exists;
    }

}
