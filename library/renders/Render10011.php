<?php

class Render10011 extends BaseListRender {
    public function __construct($controller) {
        parent::__construct($controller);
    }

    public function render($models) {
        $ret = array();

        foreach ($models as $sign => $news_model) {
            $cell = $this->serializeNewsCell($news_model);
            if (count($cell["imgs"]) == 0) {
                continue;
            }
            $ret []= $cell;
        }

        return $ret;
    } 

    protected function serializeNewsCell($news_model){
        $imgs = NewsImage::getImagesOfNews($news_model->url_sign);
        
        $ret = array(
            "title" => $news_model->title,
            "news_id" => $news_model->url_sign,
            "source" => $news_model->source_name,
            "source_url" => $news_model->source_url,
            "public_time" => $news_model->publish_time,
            "likedCount" => $news_model->liked,
            "share_url" => sprintf(SHARE_TEMPLATE, urlencode($news_model->url_sign)),
            "imgs" => array(),
        );

        $ret["tpl"] = NEWS_LIST_TPL_RAW_IMG;
        foreach($imgs as $img) {
            if (!$img || $img->is_deadlink == 1 || !$img->meta) {
                continue;
            }

            if ($img->origin_url) {
                $meta = json_decode($img->meta, true);
                if (!$meta || 
                    !is_numeric($meta["width"]) || 
                    !is_numeric($meta["height"])) {
                    continue;
                }

                $ow = $meta["width"];
                $oh = $meta["height"];
                if ($this->_os == "ios") {
                    $aw = (int) ($this->_screen_w  - 44);
                } else {
                    $aw = (int) ($this->_screen_w * 11 / 12);
                }
                
                $ah = (int) min($this->_screen_h * 0.9, $aw * $oh / $ow);

                if ($this->_net == "WIFI") {
                    $quality = IMAGE_HIGH_QUALITY;
                } else if ($this->_net == "2G") {
                    $quality = IMAGE_LOW_QUALITY;
                }else {
                    $quality = IMAGE_NORMAL_QUALITY;
                }
                
                $url =  sprintf(IMAGE_CHANNEL_IMG_PATTERN, 
                                $img->url_sign, 
                                $aw, $aw, $ah, $quality);
                $ret["imgs"][] = array(
                    "src" => $url, 
                    "height" => $ah, 
                    "width" => $aw);
            }
        }

        return $ret;
    }
}
