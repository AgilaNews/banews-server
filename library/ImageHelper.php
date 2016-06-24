<?php
define('NEWS_LIST_TPL_LARGE_IMG', 2);
define('NEWS_LIST_TPL_THREE_IMG', 3);
define('NEWS_LIST_TPL_TEXT_IMG', 4);
define('NEWS_LIST_TPL_RAW_TEXT', 5);
define('NEWS_LIST_TPL_RAW_IMG', 6);
define('IMAGE_CHANNEL_ID', 10011); //TODO change this config to read from db
define('IMAGE_CHANNEL_PATTERN', IMAGE_SERVER_NAME . IMAGE_PREFIX . "%s.jpg?p=%dX_w|c=%dX%d@0X0|q=" . IMAGE_QUALITY)

class ImageHelper {
    public static function formatNewsList($img_set, $channel_id, $screen_width, $screen_height, $dpi) {
        $ret = array(
            "imgs" => array(),
        );

        if ($channel_id == IMAGE_CHANNEL_ID) {
            $ret["tpl"] = NEWS_LIST_TPL_RAW_IMG;

            if ($channel_id == IMAGE_CHANNEL_ID) {
                foreach ($img_set as $img) {
                    if (!$img || $img->is_deadlink || !$img->meta || !$img->origin_url) {
                        continue;             
                    }

                    $meta = json_decode($img->meta, true);
                    if (!$meta || $meta["width"] || $meta["height"]) {
                        continue;
                    }
                    $ow = $meta["width"];
                    $oh = $meta["height"];
                    $aw = (int) ($screen_width * 11 / 12)
                    $ah = (int) min($screen_height * 0.9, $aw * $oh / $ow);
                    $url =  sprintf(IMAGE_CHANNEL_PATTERN, img->url_sign, $aw, $aw, $ah);
                    $ret["imgs"][] = array("src" => $url, "height" => $ah, "width" => $aw);
                }
            }

            return $ret;
        } else {
            $ret["tpl"] = NEWS_LIST_TPL_RAW_TEXT;
            if (!$img_set) {
                return $ret;
            }

            foreach($img_set as $img) {
                if (!$img || $img->is_deadlink || $img->meta) {
                    continue;
                }
                if ($img->origin_url) {
                    $meta = json_decode($img->meta, true);
                    $oh = $meta["height"];
                    $ow = $meta["width"];
                    $ret["imgs"][] = array("src" => $img->origin_url, "width" => $ow, "height" => $oh, "name" => "<!--IMG" . $img->news_pos_id . "-->");
                } else {
                    $ret["imgs"][] = array("src" => $img->source_url, "width" => 128, "height" => 128, "name" => "<!--IMG" . $img->news_pos_id . "-->"));
                }
            }

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
}
