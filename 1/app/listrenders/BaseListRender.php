<?php
define('MIN_NEWS_SEND_COUNT', 8);
define('MAX_NEWS_SENT_COUNT', 12);

define('NEWS_LIST_TPL_LARGE_IMG', 2);
define('NEWS_LIST_TPL_THREE_IMG', 3);
define('NEWS_LIST_TPL_TEXT_IMG', 4);
define('NEWS_LIST_TPL_RAW_TEXT', 5);
define('NEWS_LIST_TPL_RAW_IMG', 6);

define('BASE_CHANNEL_IMG_QUALITY', 30);
define("IMAGE_CHANNEL_IMG_PATTERN",
        IMAGE_PREFIX . 
        "/%s.jpg?p=t=%sx%s|q=" . IMAGE_CHANNEL_QUALITY);

class BaseListRender {
    public function __construct($channel_id, $device_id, 
                                $screen_width, $screen_height, 
                                $di) {
        $this->_di = $di;
        $this->_device_id = $device_id;
        $this->_channel_id = $channel_id;
        $this->_screen_w = $screen_width;
        $this->_screen_h = $screen_height;
    }

    public function getPolicyTag(){
        return "expdecay";
    }

    public function render($dispatch_id, $prefer) {
        $policy = new ExpDecayListPolicy($this->_di); 
        $required = mt_rand(MIN_NEWS_SEND_COUNT, MAX_NEWS_SENT_COUNT);
        #I don't known if 1.5 is enough
        $base = round(MAX_NEWS_SENT_COUNT * 1.5);

        $selected_news_list = $policy->sampling($this->_channel_id, $this->_device_id, 
                                                null, $base, $prefer);
        $ret = array($dispatch_id => array(), "dispatched" => array());

        $uniq = array();

        $models = News::batchGet($selected_news_list);
        foreach ($models as $sign => $news_model) {
            if ($news_model && $news_model->is_visible == 1) {
                if (array_key_exists($news_model->content_sign, $uniq) && 
                    $uniq[$news_model->content_sign]->source_name == $news_model->source_name
                   ) 
                {
                    //content sign dup and same source, continue
                    continue;
                }

                $cell = $this->serializeNewsCell($news_model);
                $ret[$dispatch_id][] = $cell;
                $ret["dispatched"][] = $sign;
                $uniq[$news_model->content_sign] = $news_model;
            }

            if (count($ret["dispatched"]) >= $required) {
                break;
            }
        }

        $policy->setDeviceSent($this->_device_id, $ret["dispatched"]);
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
                    "pattern" => sprintf(IMAGE_CHANNEL_IMG_PATTERN, $img->url_sign, "{w}", "{h}"), 
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
