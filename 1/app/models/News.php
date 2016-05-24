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

    public function getSource(){
        return "tb_news";
    }

    private static function getCacheKey($pfx, $columns) {
        $key = CACHE_NEWS_PREFIX . $pfx;

        if ($columns) {
            sort($columns);

            foreach ($columns as $column) {
                $key = $key . "_" . $column;
            }
        }
        return $key;
    }

    public static function getById($id, $columns = null) {
        $crit = array ("conditions" => "id = ?1",
                       "bind" => array (1 => $id),
                       "cache" => array(
                           "lifetime" => CACHE_USER_TTL,
                           "key" => self::getCacheKey("id_$id", $columns),
                       )
		                );
	
        if ($columns) {
            $crit["columns"] = $columns;
        }
	    
        $news_model = News::findFirst($crit);
        return $news_model;
    }
    
    public static function getBySign($sign, $columns = null) {
        $crit = array ("conditions" => "url_sign = ?1",
                       "bind" => array (1 => $sign),
                         "cache" => array (
                         "lifetime" => CACHE_USER_TTL, 
                         "key" => self::getCacheKey("sign_$sign", $columns),
                         ));

        if ($columns) {
            $crit["columns"] = $columns;
        }
        
        $news_model = News::findFirst($crit);
        return $news_model;
    }
}
