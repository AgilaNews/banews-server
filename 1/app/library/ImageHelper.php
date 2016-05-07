<?php

class ImageHelper {
    private function serializeImage($img, $use_name){
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

    public static function formatImageAndTpl($imgs){
        $ret = array(
            "imgs" => array(),
            "tpl" => NEWS_LIST_TPL_RAW_TEXT,
        );
        if (!$imgs) {
            return $ret;
        }

        $count = 0;
        if ($imgs->count() == 0) {
            $ret["tpl"] = NEWS_LIST_TPL_RAW_TEXT;
        } else if ($imgs->count() <= 2) {
            $count = 1;
            $ret["tpl"] = NEWS_LIST_TPL_TEXT_IMG;
        } else if ($imgs->count() >= 3) {
            $count = 3;
            $ret["tpl"] = NEWS_LIST_TPL_THREE_IMG;
        }

        while ($imgs->valid() && $count > 0) { 
            $ret["imgs"] []= self::serializeImage($imgs->current());
            $imgs->next();
            $count--;
        }

        return $ret;
    }
}
