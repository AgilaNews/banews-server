<?php

use Phalcon\DI;
use Phalcon\Mvc\Model\Resultset\Simple;

class NewsGif extends BaseModel {
    public $id;

    public $news_id;

    public $news_url_sign;

    public $cover_origin_url;

    public $cover_save_url;

    public $gif_origin_url;

    public $gif_save_url;

    public $gif_url_sign;

    public $update_time;

    public $is_deadlink;

    public $gif_meta;

    public static function getGifsOfNews($news_sign){
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            $value = $cache->get(CACHE_GIFS_PREFIX . $news_sign);
            if ($value) {
                $rs = unserialize($value);
                return $rs;
            }
        }

        $key = CACHE_GIFS_PREFIX . $news_sign;
        $crit = array (
            "conditions" => "news_url_sign=?1",
            "bind" => array(1 => $news_sign),
            );

        $rs = self::find($crit);
        if ($cache) {
            $cache->multi();
            $cache->set(CACHE_GIFS_PREFIX . $news_sign, serialize($rs));
            $cache->expire(CACHE_GIFS_PREFIX . $news_sign, CACHE_GIFS_TTL);
            $cache->exec();
        }

        return $rs;
    }

    public function getSource(){
        return "tb_news_gifs";
    }
}

