<?php
/**
 * 
 * @file    BannerIntervene.php
 * @authors Zhao Yulong (elysium.zyl@gmail.com)
 * @date    2016-12-04 13:07:07
 * @version $Id$
 */

use Phalcon\DI;

define('BANNER_IMAGE_SIGN_NEW', 'banner_new');
define('BANNER_IMAGE_SIGN_OLD', 'banner_old');
define('BANNER_WIDTH', 720);
define('BANNER_HEIGHT', 240); //200
define("BANNER_INTERVENE_KEY", "BANNER_INTERVENE_%s_%s_%s");
define("BANNER_INTERVENE_TTL", 14400);

class BannerIntervene extends BaseIntervene {
    public function render(){
        $news_id = $this->context["news_id"];
        $device_id = $this->context["device_id"];
        $operating_id = $this->context["operating_id"];

        if ($this->isDeviceUsed($news_id, $device_id, $operating_id)) {
            return null;
        } else {
            $news_model = News::getBySign($news_id);
            $ret = $this->serializeNewsCell($news_model);
            
            $this->setDeviceUsed($news_id, $device_id, $operating_id);
            return $ret;
        }
    }

    protected function serializeNewsCell($news_model) {
        $ret = RenderLib::GetPublicData($news_model);
        $quality = RenderLib::GetImageQuality($this->context["net"]);

        if (Features::Enabled(Features::BANNER_FEATURE, 
                                $this->context["client_version"], 
                                $this->context["os"])) {
            $ret["tpl"] = NEWS_LIST_TPL_BANNER;
            $ret["imgs"][] = array(
                "src" => sprintf(BASE_CHANNEL_IMG_PATTERN, BANNER_IMAGE_SIGN_NEW, 
                    BANNER_WIDTH, BANNER_HEIGHT, $quality), 
                "width" => BANNER_WIDTH, 
                "height" => BANNER_HEIGHT,
                "pattern" => sprintf(LARGE_CHANNEL_IMG_PATTERN, BANNER_IMAGE_SIGN_NEW, 
                    "{w}", "{h}", $quality),
                );
        } else {
            $ret["tpl"] = NEWS_LIST_TPL_LARGE_IMG;
            $ret["imgs"][] = array(
                "src" => sprintf(BASE_CHANNEL_IMG_PATTERN, BANNER_IMAGE_SIGN_OLD, 
                    660, 410, $quality), 
                "width" => 660, 
                "height" => 410,
                "pattern" => sprintf(LARGE_CHANNEL_IMG_PATTERN, BANNER_IMAGE_SIGN_OLD, 
                    "{w}", "{h}", $quality),
                );
        }

        return $ret;
    }

    protected function setDeviceUsed($news_id, $device_id, $operating_id) {
        $cache = DI::getDefault()->get('cache');
        if (!$cache) {
            return;
        }
    
        $key = sprintf(BANNER_INTERVENE_KEY, $news_id, $device_id, $operating_id);
        $cache->multi();
        $cache->set($key, 1);
        $cache->expire($key, BANNER_INTERVENE_TTL);
        $cache->exec();
    }

    protected function isDeviceUsed($news_id, $device_id, $operating_id){
        if (Features::Enabled(Features::BANNER_FEATURE, 
            $this->context["client_version"], $this->context["os"])) {
            return false;
        }

        $cache = DI::getDefault()->get('cache');
        if (!$cache) {
            return true;
        }
        
        $key = sprintf(BANNER_INTERVENE_KEY, $news_id, $device_id, $operating_id);
        $exists = $cache->exists($key);
        return $exists;
    }
}