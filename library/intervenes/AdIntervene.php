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
        case RenderLib::NEWS_LIST_TPL_AD_FB_MEDIUM:
            return $this->renderFB();
        case RenderLib::DETAIL_AD_TPL_MEDIUM:
            return $this->renderFBDetail();
        default:
            assert(false, "not implement ad type: " . $this->context["type"]);
        }
    }

    protected function renderFBDetail(){
        return array(
                     "tpl" => RenderLib::DETAIL_AD_TPL_MEDIUM,
                     "ad_id" =>  $this->getAdId(),
                     );
    }

    protected function renderFB(){
        return array(
                     "tpl" => RenderLib::NEWS_LIST_TPL_AD_FB_MEDIUM,
                     "ad_id" => $this->getAdId(),
                     );
    }

    protected function getAdId(){
        $di = DI::getDefault();
        $redis = $di->get('cache');
        $device = $this->context["device"];
        $key = sprintf(CACHE_AD_ID_KEY, "FB", $device);
        $v = 0;
            
        if ($redis) {
            $v = $redis->incr($key);
            $redis->expire($key, CACHE_AD_ID_TTL);
        }
        return $v;
    }
}
