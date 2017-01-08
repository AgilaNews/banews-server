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

    public static function getImagesOfNews($news_sign, $limit=0){
        $key = CACHE_IMAGES_PREFIX . $news_sign . $limit;
        
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            $value = $cache->get($key);
            if ($value) {
                $rs = unserialize($value);
                return $rs;
            }
        }


        $crit = array (
            "conditions" => "news_url_sign=?1",
            "bind" => array(1 => $news_sign),
            "order" => "news_pos_id",
            );
        
        if ($limit > 0){
            $crit["limit"] = $limit;
        }

        $rs = NewsImage::find($crit);
        if ($cache) {
            $cache->multi();
            $cache->set($key, serialize($rs));
            $cache->expire($key, CACHE_IMAGES_TTL);
            $cache->exec();
        }

        return $rs;
    }

    public function getSource(){
        return "tb_news_images";
    }
}

