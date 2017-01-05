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
            if (!$news_model) {
                continue;
            }
            $cell = null;
            if ($news_model instanceof TempTopIntervene) {
                $m = $news_model->render();
                if (!$m) {
                    continue;
                }
                $news_model = $m;
            } else if ($news_model instanceof BaseIntervene) {
                $r = $news_model->render();
                if ($r) {
                    $ret [] = $r; 
                }
                continue; 
            } else if ($news_model->channel_id == "30001") {
                $cell = $this->serializeVideoCell($news_model);
                if ($cell == null) {
                    continue;
                }
                if(array_key_exists($news_model->url_sign, $comment_counts)) {
                    $cell["commentCount"] = $comment_counts[$news_model->url_sign];
                }
                $cell["tag"] = "Video";
                $ret[] = $cell;
                continue;
            }

            if (!$cell) {
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
        
        return $ret;
    }

    protected function serializeVideoCell($news_model) {
        $video = Video::getByNewsSign($news_model->url_sign);
        
        if ($video) {
            $ret = RenderLib::GetPublicData($news_model);
            $ret["views"] = $video->view;
            $meta = json_decode($video->cover_meta, true);
            if (!$meta || 
                !is_numeric($meta["width"]) || 
                !is_numeric($meta["height"])) {
                continue;
            }

            $ret["imgs"][] = RenderLib::LargeImageRender($this->_net, $video->cover_image_sign,
                $meta, $this->_screen_w, $this->_screen_h, $this->_os);
            $ret["videos"][] = RenderLib::VideoRender($video, $meta, 
                $this->_screen_w, $this->_screen_h, $this->_os);
            return $ret;
        } 
        return null;
    }
}
