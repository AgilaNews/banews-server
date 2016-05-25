<?php

class News extends BaseModel {
    public $id;

    public $update_time;

    public $url_sign;

    public $source_url;

    public $channel_id;

    public $title;

    public $source_name;

    public $public_time;

    public $fetch_time;

    public $summary;

    public $json_text;

    public $ext_json_text;

    public $tag;

    public $content_sign;

    public $related_sign;

    public $display_type;

    public $shared_url;

    public $content_type;

    public $liked;

    public $is_visible;

    public function getSource(){
        return "tb_news";
    }

    private static function getCacheKey($pfx) {
        return CACHE_NEWS_PREFIX . $pfx;
    }

    public static function getById($id) {
        $crit = array ("conditions" => "id = ?1",
                       "bind" => array (1 => $id),
                       "cache" => array(
                           "lifetime" => CACHE_NEWS_TTL,
                           "key" => self::getCacheKey("id_$id"),
                       )
		                );
	
        $news_model = News::findFirst($crit);
        return $news_model;
    }
    
    public static function getBySign($sign) {
        $crit = array ("conditions" => "url_sign = ?1",
                       "bind" => array (1 => $sign),
                         "cache" => array (
                         "lifetime" => CACHE_NEWS_TTL,
                         "key" => self::getCacheKey("sign_$sign"),
                         ));

        $news_model = News::findFirst($crit);
        return $news_model;
    }
}
