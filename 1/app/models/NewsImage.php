<?php

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
        $crit = array (
            "conditions" => "news_url_sign=?1",
            "bind" => array(1 => $news_sign),
            //"order" => "news_pos_id",
            );

        return NewsImage::Find($crit);
    }

    public function getSource(){
        return "tb_news_images";
    }
}

