<?php

use Phalcon\DI;
class Render10001 extends BaseListRender {
    public function __construct($controller) {
        parent::__construct($controller);
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
            } else if ($news_model->channel_id == "30001") {
                $cell = $this->serializeVideoCell($news_model);
                if(array_key_exists($news_model->url_sign, $comment_counts)) {
                    $cell["commentCount"] = $comment_counts[$news_model->url_sign];
                }
                $cell["tag"] = "Video";
                $ret[] = $cell;
            } else {
                if ($news_model instanceof TempTopIntervene) {
                    $cell = $news_model->render();
                } else {
                    $cell = $this->serializeNewsCell($news_model);
                }

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

    protected function serializeVideoCell($news_model) {
        $ret = array(
            "title" => $news_model->title,
            "news_id" => $news_model->url_sign,
            "source" => $news_model->source_name,
            "source_url" => $news_model->source_url,
            "public_time" => $news_model->publish_time,
            "likedCount" => $news_model->liked,
            "share_url" => sprintf(SHARE_TEMPLATE, urlencode($news_model->url_sign)),
            "views" => 1000,
            "commentCount" => 0,
            "imgs" => array(),
            "videos" => array(),
            "tpl" => 10,
            );

        $video = Video::getByNewsSign($news_model->url_sign);
        if ($video) {
            $ret["views"] = $video->view;
            $meta = json_decode($video->cover_meta, true);
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

            $url = sprintf(LARGE_CHANNEL_IMG_PATTERN,
                           urlencode($video->cover_image_sign),
                           $aw, $ah, $quality);

            $ret["imgs"][] = array(
                "src" => $url,
                "width" => $aw,
                "height" => $ah,
                "pattern" => sprintf(LARGE_CHANNEL_IMG_PATTERN,
                                    urlencode($video->cover_image_sign),
                                    "{w}", "{h}", $quality),
            );

            $ret["videos"][] = array(
                "youtube_id" => $video->youtube_video_id,
                "width" => $aw,
                "height" => $ah,
                "duration" => $video->duration,
                "description" => substr($video->description, 0, VIDEO_DESCRIPTION_LIMIT),
                "display" => 0
            );
        }
        return $ret;
    }
}
