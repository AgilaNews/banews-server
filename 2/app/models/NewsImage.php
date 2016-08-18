<?php

use Phalcon\DI;
use Phalcon\Mvc\Model\Resultset\Simple;

class NewsImage extends BaseModel {
    public $id;

    public $news_id;

    public $news_pos_id;

    public $news_url_sign;

    public $url_sign;

    public $source_url;

    public $saved_url;

    public $update_time;

    
    public static function getImagesOfNews($news_sign){
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            $value = $cache->get(CACHE_IMAGES_PREFIX . $news_sign);
            if ($value) {
                $rs = unserialize($value);
                return $rs;
            }
        }

        $key = CACHE_IMAGES_PREFIX . $news_sign;
        $crit = array (
            "conditions" => "news_url_sign=?1",
            "bind" => array(1 => $news_sign),
            );

        $rs = NewsImage::find($crit);
        if ($cache) {
            $cache->multi();
            $cache->set(CACHE_IMAGES_PREFIX . $news_sign, serialize($rs));
            $cache->expire(CACHE_IMAGES_PREFIX . $news_sign, CACHE_IMAGES_TTL);
            $cache->exec();
        }

        return $rs;
    }

    public function getSource(){
        return "tb_news_images";
    }
}

