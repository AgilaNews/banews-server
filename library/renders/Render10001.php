<?php

use Phalcon\DI;
class Render10001 extends BaseListRender {
    public function __construct($controller) {
        parent::__construct($controller);
    }

    public function render($models) {
        $ret = array();

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
                $ret[] = $cell;
                continue;
            }

            if (!$cell) {
                $cell = $this->serializeNewsCell($news_model);
            }

            $ret[] = $cell;
        }

        $keys = array();
        foreach ($models as $model) {
            if (!$this->isIntervened($model)) {
                $keys []= $model->url_sign;
            }
        }

        RenderLib::FillTags($ret);
        RenderLib::FillCommentsCount($ret);
        RenderLib::FillTpl($ret, RenderLib::PLACEMENT_TIMELINE);
        
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

            $ret["imgs"][] = RenderLib::LargeImageRender($this->net, $video->cover_image_sign,
                $meta, $this->screen_w, $this->screen_h, $this->os);
            $ret["videos"][] = RenderLib::VideoRender($video, $meta, 
                $this->screen_w, $this->screen_h, $this->os);
            return $ret;
        } 
        return null;
    }
}
