<?php
/**
 * @file   AdIntervene.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Mon Oct 31 21:40:47 2016
 * 
 * @brief  
 * 
 * 
 */
use Phalcon\DI;

class AdIntervene extends BaseIntervene {
    public function render(){
        switch ($this->context["type"]) {
        case NEWS_LIST_TPL_AD_FB_MEDIUM:
            return $this->renderFB();
            
        default:
            assert(false, "not implement ad type: " . $this->context["type"]);
        }
    }

    protected function renderFB(){
        $di = DI::getDefault();
        $redis = $di->get('cache');
        $device = $this->context["device"];
        $key = sprintf(CACHE_AD_ID_KEY, "FB", $device);
            
        if ($redis) {
            $v = $redis->incr($key);
            $redis->expire($key, CACHE_AD_ID_TTL);
        }

        return array(
                     "tpl" => NEWS_LIST_TPL_AD_FB_MEDIUM,
                     "ad_id" => $v,
                     );
    }
}
