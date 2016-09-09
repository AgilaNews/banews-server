<?php
define('NEWS_LIST_TPL_LARGE_IMG', 2);
define('NEWS_LIST_TPL_THREE_IMG', 3);
define('NEWS_LIST_TPL_TEXT_IMG', 4);
define('NEWS_LIST_TPL_RAW_TEXT', 5);
define('NEWS_LIST_TPL_RAW_IMG', 6);
define('NEWS_LIST_TPL_VIDEO', 7);
define('LARGE_IMAGE_MAX_COUNT', 3);
define('LARGE_IMAGE_MIN_WH_RATIO', 1.6);
define('LARGE_IMAGE_MAX_WH_RATIO', 2.4);

class BaseListRender {
    public function __construct($controller) {
        $this->_device_id = $controller->deviceId;
        $this->_screen_w = $controller->resolution_w;
        $this->_screen_h = $controller->resolution_h;
        $this->_net = $controller->net;
        $this->_os = $controller->os;
        $this->_client_version = $controller->client_version;
        $this->_large_img_count = 0;
    }

    public function render($models) {
        $ret = array();
        $max_quality = 0.0;
        $news_sign = "";

        foreach ($models as $sign => $news_model) {
            $cell = $this->serializeNewsCell($news_model);
            $ret[] = $cell;
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
       
        if ($this->_net == "WIFI") {
            $quality = IMAGE_HIGH_QUALITY;
        } else if ($this->_net == "2G") {
            $quality = IMAGE_LOW_QUALITY;
        } else {
            $quality = IMAGE_NORMAL_QUALITY;
        }

        $ret["tpl"] = NEWS_LIST_TPL_RAW_TEXT; 
        $isLarge = False;
        foreach ($imgs as $img) {
            if (!$img || $img->is_deadlink == 1 || !$img->meta) {
                continue;
            }

            if ($img->origin_url) {
                $meta = json_decode($img->meta, true);
                $oh = $meta["height"];
                $ow = $meta["width"];
                $isLarge = $this->isLargeImageNews($meta); 
                if ($isLarge){
                    $pattern =  sprintf(LARGE_CHANNEL_IMG_PATTERN, $img->url_sign, "{w}", "{h}", $quality); 
                }
                else{
                    $pattern =  sprintf(BASE_CHANNEL_IMG_PATTERN, $img->url_sign, "{w}", "{h}", $quality); 
                }

                $ret["imgs"][] = array(
                    "src" => sprintf(BASE_CHANNEL_IMG_PATTERN, $img->url_sign, "225", "180", $quality), 
                    "width" => $ow, 
                    "height" => $oh, 
                    "pattern" => $pattern, 
                    "name" => "<!--IMG" . $img->news_pos_id . "-->"
                    );
            }

            if ($isLarge) {
                break;
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

        if ($isLarge) {
            $ret["tpl"] = NEWS_LIST_TPL_LARGE_IMG;
        }

        return $ret;
    } 

    protected function isLargeImageNews($img) {
        if($this->_large_img_count > LARGE_IMAGE_MAX_COUNT || version_compare($this->_client_version, "1.1.3", "<")){
            return False;
        }
        $quality = $this->getImageQuality($img);
        if ($quality > 0.0 and rand(1,10) > 2){
            $this->_large_img_count += 1;
            return True;
        }
        return False;
    }

    protected function getImageQuality($img) {
        if (!$img){
            return 0.0;
        }
        $oh = (float)$img["height"];
        $ow = (float)$img["width"];
        if ($oh==0){
            return 0.0;
        }
        $rate = $ow/$oh;
        if ($rate < LARGE_IMAGE_MIN_WH_RATIO or $rate > LARGE_IMAGE_MAX_WH_RATIO){
            return 0.0;
        }
        return $rate;
    }
}
