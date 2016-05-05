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


    public function getSource(){
        return "tb_news";
    }

    public static function getById($id, $columns = null) {
        $crit = array ("conditions" => "id = ?1",
                       "bind" => array (1 => $id),
                       /*
                         "cache" => array (
                         "lifetime" => $this->config->cache->general_life_time,
                         "key" => $this->config->cache->keys->news,
                         )*/);
        if ($columns) {
            $crit["columns"] = $columns;
        }
        
        $news_model = News::findFirst($crit);
        return $news_model;
    }
    
    public static function getBySign($sign, $colunms = null) {
        $crit = array ("conditions" => "url_sign = ?1",
                       "bind" => array (1 => $sign),
                       /*
                         "cache" => array (
                         "lifetime" => $this->config->cache->general_life_time,
                         "key" => $this->config->cache->keys->news,
                         )*/);
        if ($columns) {
            $crit["columns"] = $columns;
        }
        
        $news_model = News::findFirst($crit);
        return $news_model;
    }
}
