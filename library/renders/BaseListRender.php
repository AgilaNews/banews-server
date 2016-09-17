<?php
define('NEWS_LIST_TPL_LARGE_IMG', 2);
define('NEWS_LIST_TPL_THREE_IMG', 3);
define('NEWS_LIST_TPL_TEXT_IMG', 4);
define('NEWS_LIST_TPL_RAW_TEXT', 5);
define('NEWS_LIST_TPL_RAW_IMG', 6);
define('NEWS_LIST_TPL_VIDEO', 7);
define('NEWS_LIST_TPL_BIG_YOUTUBE', 10);
define('NEWS_LIST_TPL_SMALL_YOUTUBE', 11);

define('NEWS_LIST_TPL_NBA', 1000);

define('LARGE_IMAGE_MAX_COUNT', 3);
define('LARGE_IMAGE_MIN_WH_RATIO', 1.6);
define('LARGE_IMAGE_MAX_WH_RATIO', 2.4);

define('MAX_HOT_TAG', 1);

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
        $hot_tags = 0;

        foreach ($models as $sign => $news_model) {
            $cell = $this->serializeNewsCell($news_model);
            
            if ($hot_tags < MAX_HOT_TAG && mt_rand(0, 2) == 1) {
                $cell["tag"] = "hot";
                $hot_tags++;
            } else {
                $cell["tag"] = "";
            }
            $ret[] = $cell;
        }
        return $ret;
    }

    protected function serializeNewsCell($news_model) {
        if (version_compare($this->_client_version, VIDEO_NEWS_FEATURE, ">=")) {
            $videos = NewsYoutubeVideo::getVideosOfNews($news_model->url_sign);
        } else {
            $videos == null;
        }

        $commentCount = Comment::getCount($news_model->id);

        $ret = array (
            "title" => $news_model->title,
            "commentCount" => $commentCount,
            "news_id" => $news_model->url_sign,
            "source" => $news_model->source_name,
            "source_url" => $news_model->source_url,
            "public_time" => $news_model->publish_time,
        );
        
        if ($videos && $videos->count() != 0) {
            foreach ($videos as $v) {
                if (!$v || $v->is_deadlink == 1 ||
                    !$v->cover_meta ||
                    !$v->cover_origin_url) {
                    continue;
                }

                $cover_meta = json_decode($v->cover_meta, true);
                if ($cover_meta || !$cover_meta["width"] || !$cover_meta["height"]) {
                    $video = $v;
                    break;
                }
            }

            if (!$video || !$cover_meta) {
                $ret["imgs"] = array();
            } else {
                if ($this->useLargeVideo($video)) {
                    $ret["tpl"] = NEWS_LIST_TPL_BIG_YOUTUBE;
                    $cell = $this->getImgCell($video->video_url_sign, $cover_meta, true);
                } else {
                    $ret["tpl"] = NEWS_LIST_TPL_SMALL_YOUTUBE;
                    $cell = $this->getImgCell($video->video_url_sign, $cover_meta, false);
                }
                $ret["imgs"] = array($cell);
            }
        } else {
            $ret["tpl"] = NEWS_LIST_TPL_RAW_TEXT;
            $ret["imgs"] = array();
            $usedLarge = false;

            $imgs = NewsImage::getImagesOfNews($news_model->url_sign);
            
            foreach ($imgs as $img) {
                if (!$img || $img->is_deadlink == 1 || !$img->meta) {
                    continue;
                }
                
                if ($img->origin_url) {
                    $meta = json_decode($img->meta, true);
                    
                    if ($this->useLargeImageNews($meta)){
                        //replaced all imgs, only take the big one
                        $cell = $this->getImgCell($img->url_sign, $meta, true);
                        $cell["name"] = "<!--IMG" . $img->news_pos_id . "-->";
                        $ret["imgs"] = array($cell);
                        $usedLarge = true;
                        break;
                    } else{
                        $cell = $this->getImgCell($img->url_sign, $meta, false);
                        $cell["name"] = "<!--IMG" . $img->news_pos_id . "-->";
                        $ret["imgs"] []= $cell;
                    }
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

            if ($usedLarge) {
                $ret["tpl"] = NEWS_LIST_TPL_LARGE_IMG;
            }
        }
        
        return $ret;
    } 
    
    protected function useLargeImageNews($img) {
        if($this->_large_img_count > LARGE_IMAGE_MAX_COUNT ||
           version_compare($this->_client_version, LARGE_IMG_FEATURE, "<")) {
            return false;
        }
        
        $quality = $this->getImageQuality($img);
        if ($quality > 0.0 and rand(1,10) > 2){
            $this->_large_img_count += 1;
            return true;
        }
        return false;
    }

    protected function getImageQuality($img) {
        if (!$img){
            return 0.0;
        }
        $oh = $img["height"];
        $ow = $img["width"];
        
        if ($oh == 0){
            return 0.0;
        }
        $rate = $ow/$oh;
        if ($rate < LARGE_IMAGE_MIN_WH_RATIO or $rate > LARGE_IMAGE_MAX_WH_RATIO){
            return 0.0;
        }
        return $rate;
    }

    protected function useLargeVideo($video) {
        return false;
    }

    protected function getImgCell($url_sign, $meta, $large) {
        if ($this->_net == "WIFI") {
            $quality = IMAGE_HIGH_QUALITY;
        } else if ($this->_net == "2G") {
            $quality = IMAGE_LOW_QUALITY;
        } else {
            $quality = IMAGE_NORMAL_QUALITY;
        }

        $oh = $meta["height"];
        $ow = $meta["width"];

        $cell = array(
                      "src" => sprintf(BASE_CHANNEL_IMG_PATTERN, $url_sign, "225", "180", $quality), 
                      "width" => $ow, 
                      "height" => $oh, 
                      );
        
        if ($large) {
            $cell["pattern"] = sprintf(LARGE_CHANNEL_IMG_PATTERN, $url_sign, "{w}", "{h}", $quality);
        } else {
            $cell["pattern"] = sprintf(BASE_CHANNEL_IMG_PATTERN, $url_sign, "{w}", "{h}", $quality);
        }

        return $cell;

    }
}
