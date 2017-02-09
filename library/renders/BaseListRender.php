<?php

use Phalcon\DI;
define('LARGE_IMAGE_MAX_COUNT', 3);
define('LARGE_IMAGE_MIN_WH_RATIO', 1.6);
define('LARGE_IMAGE_MAX_WH_RATIO', 2.4);

class BaseListRender {
    public function __construct($controller, $placement_id = null) {
        $this->device_id = $controller->deviceId;
        $this->screen_w = $controller->resolution_w;
        $this->screen_h = $controller->resolution_h;
        $this->net = $controller->net;
        $this->os = $controller->os;
        $this->client_version = $controller->client_version;
        $this->large_img_count = 0;
        $this->placement_id = $placement_id;
    }
    
    public function render($models) {
        $ret = array();

        foreach ($models as $news_model) {
            if (!$news_model) {
                continue;
            }
            
            if ($news_model instanceof AdIntervene) {
                $r = $news_model->render();
                if ($r) {
                    $ret [] = $r; 
                }
            } else {
                $cell = $this->serializeNewsCell($news_model);
                $ret[] = $cell;
            }
        }

        $keys = array();
        foreach ($models as $news_model) {
            if ($news_model instanceof News) {
                $keys []= $news_model->url_sign;
            }
        }
        
        RenderLib::FillTags($ret);
        RenderLib::FillCommentsCount($ret);
        RenderLib::FillTpl($ret, $this->placement_id, RenderLib::PLACEMENT_TIMELINE);

        return $ret;
    }

    protected function serializeNewsCell($news_model) {
        $usedLarge = false;

        if (Features::Enabled(Features::VIDEO_NEWS_FEATURE, $this->client_version, $this->os)) {
            $videos = NewsYoutubeVideo::getVideosOfNews($news_model->url_sign);
        } else {
            $videos = null;
        }
        
        $ret = RenderLib::GetPublicData($news_model);
        
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
                //hardcode 58 for render play button in list, reconstruct later
                $cell = RenderLib::ImageRender($this->net, $video->video_url_sign, $cover_meta, false, true);
                $ret["imgs"] = array($cell);
            }
        } else {
            $ret["imgs"] = array();
            $imgs = NewsImage::getImagesOfNews($news_model->url_sign, 3);
            
            foreach ($imgs as $img) {
                if (!$img || $img->is_deadlink == 1 || !$img->meta) {
                    continue;
                }
                
                if ($img->origin_url) {
                    $meta = json_decode($img->meta, true);
                    
                    if ($this->useLargeImageNews($meta)){
                        //replaced all imgs, only take the big one
                        $cell = RenderLib::ImageRender($this->net, $img->url_sign, $meta, true);
                        $cell["name"] = "<!--IMG" . $img->news_pos_id . "-->";
                        $ret["__large_image"] = true;
                        $ret["imgs"] = array($cell);
                        break;
                    } else{
                        $cell = RenderLib::ImageRender($this->net, $img->url_sign, $meta, false);
                        $cell["name"] = "<!--IMG" . $img->news_pos_id . "-->";
                        $ret["imgs"] []= $cell;
                    }
                }
            }
        }

        $ret["filter_tags"] = RenderLib::GetFilter($news_model->source_name);

        return $ret;
    } 
    
    protected function useLargeImageNews($img) {
        if($this->large_img_count > LARGE_IMAGE_MAX_COUNT ||
           !Features::Enabled(Features::LARGE_IMG_FEATURE, $this->client_version, $this->os)) {
               return false;
           }
           
        
        $quality = $this->getImageQuality($img);
        if ($quality > 0.0){
            $this->large_img_count += 1;
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
        
        $rate = $ow / $oh;
        if ($rate < LARGE_IMAGE_MIN_WH_RATIO || $rate > LARGE_IMAGE_MAX_WH_RATIO){
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

    public static function getRender($controller, $channel_id, $placement_id = null) {
        if (RenderLib::isVideoChannel($channel_id)) {
            return new VideoListRender($controller, $placement_id);
        }
        if (RenderLib::isGifChannel($channel_id)) {
            return new GifListRender($controller, $placement_id);
        }
        if (RenderLib::isPhotoChannel($channel_id)) {
            return new PhotoListRender($controller, $placement_id);
        }

        if ($channel_id == "10013") {
            return new NbaListRender($controller, $placement_id);
        }

        if ($channel_id == "10001") {
            return new HotListRender($controller, $placement_id);
        }

        return new BaseListRender($controller, $placement_id);
    }
}
