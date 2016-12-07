<?php


use Phalcon\DI;
define('LARGE_IMAGE_MAX_COUNT', 3);
define('LARGE_IMAGE_MIN_WH_RATIO', 1.6);
define('LARGE_IMAGE_MAX_WH_RATIO', 2.4);

define('MAX_HOT_TAG', 2);
define('HOT_LIKE_THRESHOLD', 3);

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
        $di = DI::getDefault();
        $comment_service = $di->get('comment');
        $config = $di->get('config');
        
        $ret = array();
        $max_quality = 0.0;
        $news_sign = "";
        $hot_tags = 0;

        $keys = array();
        foreach ($models as $model) {
            if (!$this->isIntervened($model)) {
                $keys []= $model->url_sign;
            }
        }
        
        $comment_counts = Comment::getCount($keys);
        
        foreach ($models as $news_model) {
            if ($news_model instanceof AdIntervene) {
                $r = $news_model->render();
                if ($r) {
                    $ret [] = $r; 
                }
            } else {
                $cell = $this->serializeNewsCell($news_model);
                
                if(array_key_exists($news_model->url_sign, $comment_counts)) {
                    $cell["commentCount"] = $comment_counts[$news_model->url_sign];
                }
                
                if ($hot_tags < MAX_HOT_TAG && $news_model->liked >= HOT_LIKE_THRESHOLD) {
                    if (mt_rand() % 3 == 0) {
                        $cell["tag"] = "Hot";
                    }
                    $hot_tags++;
                } else {
                    $cell["tag"] = "";
                }
                $ret[] = $cell;
            }
        }
        
        return $ret;
    }

    protected function serializeNewsCell($news_model) {
        if (Features::Enabled(Features::VIDEO_NEWS_FEATURE, $this->_client_version, $this->_os)) {
            $videos = NewsYoutubeVideo::getVideosOfNews($news_model->url_sign);
        } else {
            $videos = null;
        }
        
        $ret = RenderLib::GetPublicData($news_model);
        $ret["filter_tags"] = RenderLib::GetFilter($news_model->source_name);
        
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
                    $cell = RenderLib::ImageRender($this->_net, $video->video_url_sign, $cover_meta, true);
                } else {
                    $ret["tpl"] = NEWS_LIST_TPL_SMALL_YOUTUBE;
                    $cell = RenderLib::ImageRender($this->_net, $video->video_url_sign, $cover_meta, true);
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
                        $cell = RenderLib::ImageRender($this->_net, $img->url_sign, $meta, true);
                        $cell["name"] = "<!--IMG" . $img->news_pos_id . "-->";
                        $ret["imgs"] = array($cell);
                        $usedLarge = true;
                        break;
                    } else{
                        $cell = RenderLib::ImageRender($this->_net, $img->url_sign, $meta, true);
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
           !Features::Enabled(Features::LARGE_IMG_FEATURE, $this->_client_version, $this->_os)) {
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
    
    protected function isIntervened($model) {
        return $model instanceof BaseIntervene;
    }

}
