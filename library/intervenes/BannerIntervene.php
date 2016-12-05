<?php
/**
 * 
 * @file    BannerIntervene.php
 * @authors Zhao Yulong (elysium.zyl@gmail.com)
 * @date    2016-12-04 13:07:07
 * @version $Id$
 */

use Phalcon\DI;

define('BANNER_IMAGE_SIGN', 'WabhTzb6bbs=');
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
            $ret["imgs"] = array(
                "src" => sprintf(BASE_CHANNEL_IMG_PATTERN, BANNER_IMAGE_SIGN, BANNER_WIDTH, BANNER_HEIGHT, $quality), 
                "width" => BANNER_WIDTH, 
                "height" => BANNER_HEIGHT,
                $cell["pattern"] = sprintf(LARGE_CHANNEL_IMG_PATTERN, BANNER_IMAGE_SIGN, "{w}", "{h}", $quality),
                );
            return $ret;
        } 

        $imgs = NewsImage::getImagesOfNews($news_model->url_sign);
        foreach ($imgs as $img) {
            if (!$img || $img->is_deadlink == 1 || !$img->meta) {
                continue;
            }

            if ($img->origin_url) {
                $meta = json_decode($img->meta, true);
                $cell = RenderLib::ImageRender($this->_net, $img->url_sign, $meta, true);
                $cell["name"] = "<!--IMG" . $img->news_pos_id . "-->";
                $ret["imgs"] []= $cell;
            }
        }

        if (count($ret["imgs"]) == 0) {
            $ret["tpl"] = NEWS_LIST_TPL_RAW_TEXT;
        } else if (count($ret["imgs"]) <= 2) {
            $ret["imgs"] = array_slice($ret["imgs"], 0 ,1);
            $ret["tpl"] = NEWS_LIST_TPL_TEXT_IMG;
        } else if (count($ret["imgs"]) >= 3) {
            $ret["imgs"] = array_slice($ret["imgs"], 0 ,3);
            $ret["tpl"] = NEWS_LIST_TPL_THREE_IMG;
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