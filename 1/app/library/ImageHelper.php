<?php
define('NEWS_LIST_TPL_LARGE_IMG', 2);
define('NEWS_LIST_TPL_THREE_IMG', 3);
define('NEWS_LIST_TPL_TEXT_IMG', 4);
define('NEWS_LIST_TPL_RAW_TEXT', 5);

class ImageHelper {
    protected static function selectImg($img, $model, $is_thumb) {
        $img_obj = json_decode($img->saved_url, true);
        if (!$img_obj){
            return array(
                         "src"=> $img->source_url,
                         "height"=> 128,
                         "width"=> 128,
                         );
        }
        
        if ($is_thumb) {
            $model = "thumb_" . $model;
        }
        
        return $img_obj[$model];
    }
    
    public static function formatImgs($imgs, $model, $is_thumb, $use_name = true) {
        $ret = array();

        foreach ($imgs as $img) {
            $cell = self::selectImg($img, $model, $is_thumb);
            if ($use_name) {
                $cell["name"] = "<!--IMG" . $img->news_pos_id . '-->';
            }
            $ret []= $cell;
        }

        return $ret;
    }

    public static function formatImageAndTpl($img_set, $model, $is_thumb){
        $ret = array(
            "imgs" => array(),
            "tpl" => NEWS_LIST_TPL_RAW_TEXT,
        );
        if (!$img_set) {
            return $ret;
        }

        $ret["imgs"][] = self::formatImgs($img_set, $model, $is_thumb, false);
        
        $imgs = array();
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
}
