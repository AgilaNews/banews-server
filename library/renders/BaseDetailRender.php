<?php
/**
 * @file   BaseDetailRender.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Fri Jan  6 20:18:12 2017
 * 
 * @brief  
 * 
 * 
 */
class BaseDetailRender {
    public static function getRenderByChannel($channel_id, $controller) {
        if (RenderLib::isVideoChannel($channel_id)) {
            return new DetailVideoRender($controller);
        }
        if (RenderLib::isPhotoChannel($channel_id)) {
            return new DetailPhotoRender($controller);
        }
        if (RenderLib::isGifChannel($channel_id)) {
            return new DetailGifRender($controller);
        }

        return new BaseDetailRender($controller);
    }
    
    public function __construct($controller) {
        $this->c = $controller;
    }

    protected function getPublic($news_model) {
        $ret = array(
            "body" => $news_model->json_text,
            "recommend_news" => array(),
            "news_id" => $news_model->url_sign,
            "title" => $news_model->title,
            "source" => $news_model->source_name,
            "source_url" => $news_model->source_url,
            "public_time" => $news_model->publish_time,
            "share_url" => sprintf(SHARE_TEMPLATE, urlencode($news_model->url_sign)),
            "channel_id" => $news_model->channel_id,
            "likedCount" => $news_model->liked,
            "collect_id" => 0, 
            "ad" => new stdClass(),
        );

        if (Features::Enabled(Features::AD_FEATURE, $this->c->client_version, $this->c->os)) {
            $intervene = new AdIntervene(array(
                                               "type" => RenderLib::DETAIL_AD_TPL_MEDIUM,
                                               "device" => $this->c->deviceId,
                                               ));

            $ret["ad"] = $intervene->render();
        }

        return $ret;
    }

    protected function fillComment($news_model, &$ret) {
        $commentCount = Comment::getCount(array($news_model->url_sign));
        
        $ret["commentCount"] = $commentCount[$news_model->url_sign];
        $topNewComment = Comment::getCommentByFilter($this->c->deviceId, $news_model->url_sign, 0, 5, "new");
        if (Features::Enabled(Features::RICH_COMMENT_FEATURE, $this->c->client_version, $this->c->os)) {
            $topHotComment = Comment::getCommentByFilter($this->c->deviceId, $news_model->url_sign, 0, 10, "hot");
            $ret["comments"] = array(
                                     "new" => $topNewComment,
                                     "hot" => $topHotComment,
                                     );
        } else {
            $ret["comments"] = $topNewComment;
        }
    }

    protected function fillVideos($news_model, &$ret) {
        $videos = NewsYoutubeVideo::getVideosOfNews($news_model->url_sign);
        $videocell = array();

        if (Features::Enabled(Features::VIDEO_NEWS_FEATURE, $this->c->client_version, $this->c->os)) {
            foreach($videos as $video) {
                if (!$video || $video->is_deadlink == 1 || !$video->cover_meta) {
                    continue;
                }
                
                if ($video->cover_origin_url) {
                    $cover_meta = json_decode($video->cover_meta, true);
                    if (!$cover_meta || !$cover_meta["width"] || !$cover_meta["height"]) {
                        continue;
                    }
                }

                $c = RenderLib::LargeImageRender(LARGE_CHANNEL_IMG_PATTERN,
                                                 $this->c->net, $video->video_url_sign, $cover_meta, $this->c->resolution_w,
                                                 $this->c->resolution_h, $this->c->os, true, true);
                $c["video_pattern"] = $c["pattern"];
                $c["youtube_id"] = $video->youtube_video_id;
                $c["name"] = "<!--YOUTUBE" . $video->news_pos_id . "-->";
                $videocell []= $c;
            }

            $ret["youtube_videos"] = $videocell;
        }
    }

    protected function fillImgs($news_model, &$ret) {
        $imgs = NewsImage::getImagesOfNews($news_model->url_sign);
        $imgcell = array();

        foreach ($imgs as $img) {
            if (!$img || $img->is_deadlink == 1 || !$img->meta) {
                continue;
            }
            
            if ($img->origin_url) {
                $meta = json_decode($img->meta, true);
                if (!$meta || !$meta["width"] || !$meta["height"]) {
                    continue;
                }
            }
            
            $c = RenderLib::LargeImageRender(LARGE_CHANNEL_IMG_PATTERN,
                                             $this->c->net, $img->url_sign, $meta, $this->c->resolution_w,
                                             $this->c->resolution_h, $this->c->os);
            $c["name"] = "<!--IMG" . $img->news_pos_id . "-->";
            $imgcell[] = $c;
        }
        $ret["imgs"] = $imgcell;

    }

    protected function fillRecommend($news_model, $recommend_models, &$ret) {
        $cache = $this->c->di->get('cache');
        $cname = "Recommend" . $news_model->channel_id;
        if (class_exists($cname)) {
            $render = new $cname($this->c);
        } else {
            $render = new BaseRecommendRender($this->c);
        }

        $ret["recommend_news"] = $render->render($recommend_models);
        return $ret;
    }

    protected function fillCollectInfo($news_model, &$ret) {
        if ($this->c->userSign) {
            $ret["collect_id"] = Collect::getCollectId($this->c->userSign, $news_model->url_sign);
        }
    }
    
    public function render($news_model, $recommend_models = null) {
        $ret = $this->getPublic($news_model);

        $this->fillComment($news_model, $ret);
        $this->fillVideos($news_model, $ret);
        $this->fillImgs($news_model, $ret);
        if ($recommend_models) {
            $this->fillRecommend($news_model, $recommend_models, $ret);
        }
        $this->fillCollectInfo($news_model, $ret);
        
        return $ret;
    }
}
