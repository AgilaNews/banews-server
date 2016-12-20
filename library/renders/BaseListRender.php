<?php


use Phalcon\DI;
define('LARGE_IMAGE_MAX_COUNT', 3);
define('LARGE_IMAGE_MIN_WH_RATIO', 1.6);
define('LARGE_IMAGE_MAX_WH_RATIO', 2.4);

define('MAX_HOT_TAG', 2);
define('HOT_LIKE_THRESHOLD', 3);

class BaseListRender {

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
            $keys []= $model->url_sign;
        }
        
        $comment_counts = Comment::getCount($keys);
        
        foreach ($models as $news_model) {
            if(!$news_model) {
                continue;
            }
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
        $videos = NewsYoutubeVideo::getVideosOfNews($news_model->url_sign);
        
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
                $ret["tpl"] = NEWS_LIST_TPL_SMALL_YOUTUBE;
                $cell = RenderLib::ImageRender("WIFI", $video->video_url_sign, $cover_meta, false);
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
                        $cell = RenderLib::ImageRender("WIFI", $img->url_sign, $meta, true);
                        $cell["name"] = "<!--IMG" . $img->news_pos_id . "-->";
                        $ret["imgs"] = array($cell);
                        $usedLarge = true;
                        break;
                    } else{
                        $cell = RenderLib::ImageRender("WIFI", $img->url_sign, $meta, false);
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
        return false;
    }

    protected function useLargeVideo($video) {
        return false;
    }
    
    protected function isIntervened($model) {
        return $model instanceof BaseIntervene;
    }
}
