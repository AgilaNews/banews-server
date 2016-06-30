<?php
define('NEWS_LIST_TPL_LARGE_IMG', 2);
define('NEWS_LIST_TPL_THREE_IMG', 3);
define('NEWS_LIST_TPL_TEXT_IMG', 4);
define('NEWS_LIST_TPL_RAW_TEXT', 5);
define('NEWS_LIST_TPL_RAW_IMG', 6);
define('NEWS_LIST_TPL_VIDEO', 7);
class BaseListRender {
    public function __construct($device_id, $screen_width, $screen_height) {
        $this->_device_id = $device_id;
        $this->_screen_w = $screen_width;
        $this->_screen_h = $screen_height;
    }

    public function render($models) {
        $ret = array();

        foreach ($models as $sign => $news_model) {
            $cell = $this->serializeNewsCell($news_model);
            $ret []= $cell;
        }

        return $ret;
    }

    protected function serializeNewsCell($news_model) {
        $imgs = NewsImage::getImagesOfNews($news_model->url_sign);
        $commentCount = Comment::getCount($news_model->id);

        $ret = array (
            "title" => $news_model->title,
            "commentCount" => $commentCount,
            "news_id" => $news_model->url_sign,
            "source" => $news_model->source_name,
            "source_url" => $news_model->source_url,
            "public_time" => $news_model->publish_time,
            "imgs" => array(),
        );
        
        $ret["tpl"] = NEWS_LIST_TPL_RAW_TEXT; 
        foreach ($imgs as $img) {
            if (!$img || $img->is_deadlink == 1 || !$img->meta) {
                continue;
            }

            if ($img->origin_url) {
                $meta = json_decode($img->meta, true);
                $oh = $meta["height"];
                $ow = $meta["width"];
                $ret["imgs"][] = array(
                    "src" => $img->origin_url, 
                    "width" => $ow, 
                    "height" => $oh, 
                    "pattern" => sprintf(BASE_CHANNEL_IMG_PATTERN, $img->url_sign, "{w}", "{h}"), 
                    "name" => "<!--IMG" . $img->news_pos_id . "-->"
                );
            } else {
                // TODO
                // if picuture is not saved, we will not consider to use this image
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
