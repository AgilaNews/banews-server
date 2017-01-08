<?php

use Phalcon\DI;
class RenderTopicNews extends BaseListRender {
    public function __construct($controller) {
        parent::__construct($controller);
    }

    public function render($models, $from) {
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

        if ($from == 0) {
            $first_medel = array_shift($models);
            $cell = $this->serializeNewsCell($first_medel, true);
            if(array_key_exists($first_medel->url_sign, $comment_counts)) {
                $cell["commentCount"] = $comment_counts[$first_medel->url_sign];
            }
            $ret[] = $cell;
        }
        
        foreach ($models as $news_model) {
            $cell = null;
            if (!$news_model) {
                continue;
            }
            if ($news_model->channel_id == VIDEO_CHANNEL_ID) {
                $cell = $this->serializeVideoCell($news_model);
                if ($cell == null) {
                    continue;
                }
                $cell["tag"] = "Video";
            } else {
                $cell = $this->serializeNewsCell($news_model);
                /*           
                if ($hot_tags < MAX_HOT_TAG && $news_model->liked >= HOT_LIKE_THRESHOLD) {
                    if (mt_rand() % 3 == 0) {
                        $cell["tag"] = "Hot";
                    }
                    $hot_tags++;
                } else {
                    $cell["tag"] = "";
                }
                */
            }

            if(array_key_exists($news_model->url_sign, $comment_counts)) {
                $cell["commentCount"] = $comment_counts[$news_model->url_sign];
            }

            $ret[] = $cell;
        }
        return $ret;
    }

    protected function serializeNewsCell($news_model, $largeimage = false) {
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
                $ret["tpl"] = NEWS_LIST_TPL_SMALL_YOUTUBE;
                $cell = RenderLib::ImageRender($this->net, $video->video_url_sign, $cover_meta, false);
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
                    
                    if ($largeimage){
                        //replaced all imgs, only take the big one
                        $cell = RenderLib::ImageRender($this->net, $img->url_sign, $meta, true);
                        $cell["name"] = "<!--IMG" . $img->news_pos_id . "-->";
                        $ret["imgs"] = array($cell);
                        $usedLarge = true;
                        break;
                    } else{
                        $cell = RenderLib::ImageRender($this->net, $img->url_sign, $meta, false);
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

    protected function serializeVideoCell($news_model) {
        $video = Video::getByNewsSign($news_model->url_sign);
        if ($video) {
            $ret = RenderLib::GetPublicData($news_model);
            $ret["tpl"] = NEWS_LIST_TPL_VIDEO_SMALL;
            $ret["views"] = $video->view;
            $meta = json_decode($video->cover_meta, true);
            if (!$meta || 
                !is_numeric($meta["width"]) || 
                !is_numeric($meta["height"])) {
                continue;
            }

            $ret["imgs"][] = RenderLib::ImageRender($this->net, $video->cover_image_sign,
                $meta, false);
            $ret["videos"][] = RenderLib::VideoRender($video, $meta, 
                $this->screen_w, $this->screen_h, $this->os);
            return $ret;
        } 
        return null;
    }
}
