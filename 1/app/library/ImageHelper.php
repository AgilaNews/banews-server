<?php
define('NEWS_LIST_TPL_LARGE_IMG', 2);
define('NEWS_LIST_TPL_THREE_IMG', 3);
define('NEWS_LIST_TPL_TEXT_IMG', 4);
define('NEWS_LIST_TPL_RAW_TEXT', 5);

class ImageHelper {
    private static function serializeImage($img, $use_name){
        $ret =  array (
            "src" => $img->saved_url ? $img->saved_url : $img->source_url,
            "width" => 128, // TODO
            "height" => 128,
        );
        if ($use_name) {
            $ret["name"] = "<!--IMG" . $img->news_pos_id . '-->';
        }
        return $ret;
    }

    public static function formatImgs($imgs) {
        $ret = array();

        foreach ($imgs as $img) {
            $ret []= self::serializeImage($img, true);
        }

        return $ret;
    }

    public static function formatImageAndTpl($img_set){
        $ret = array(
            "imgs" => array(),
            "tpl" => NEWS_LIST_TPL_RAW_TEXT,
        );
        if (!$img_set) {
            return $ret;
        }
        
        $imgs = array();
        foreach($img_set as $img_single_set) {
            $imgs []= $img_single_set;
        }

        if (count($imgs) == 0) {
            $ret["tpl"] = NEWS_LIST_TPL_RAW_TEXT;
        } else if (count($imgs) <= 2) {
            $imgs = array_slice($imgs, 0 ,1);
            $ret["tpl"] = NEWS_LIST_TPL_TEXT_IMG;
        } else if (count($imgs) >= 3) {
            $imgs = array_slice($imgs, 0 ,3);
            $ret["tpl"] = NEWS_LIST_TPL_THREE_IMG;
        }

        foreach ($imgs as $img) {
            $ret["imgs"] []= self::serializeImage($img, false);
        }

        return $ret;
    }
}
