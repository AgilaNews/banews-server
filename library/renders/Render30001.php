<?php
/**
 * 
 * @file    Render30001.php
 * @authors Zhao Yulong (elysium.zyl@gmail.com)
 * @date    2016-10-27 21:35:42
 * @version $Id$
 */

class Render30001 extends BaseListRender {
    public function __construct($controller) {
        parent::__construct($controller);
    }

    public function render($models) {
        $ret = array();

        $keys = array();
        foreach ($models as $model) {
            if ($model && !$this->isIntervened($model)) {
                $keys []= $model->url_sign;
            }
        }

        foreach ($models as $sign => $news_model) {
            if (!$news_model) {
                continue;
            }
            $cell = $this->serializeNewsCell($news_model);
            if ($cell == null) {
                continue;
            }
            $ret []= $cell;
        }

        RenderLib::FillCommentsCount($ret);
        RenderLib::FillTpl($ret, RenderLib::PLACEMENT_TIMELINE);
        return $ret;
    } 

    public function serializeNewsCell($news_model) {
        $video = Video::getByNewsSign($news_model->url_sign);
        if ($video) {
            $ret = RenderLib::GetPublicData($news_model);
            $ret["views"] = $video->view;
            $meta = json_decode($video->cover_meta, true);
            if (!$meta || 
                !is_numeric($meta["width"]) || 
                !is_numeric($meta["height"])) {
                return null;
            }

            $ret["imgs"][] = RenderLib::LargeImageRender($this->net, $video->cover_image_sign,
                $meta, $this->screen_w, $this->screen_h, $this->os);
            $ret["videos"][] = RenderLib::VideoRender($video, $meta, $this->screen_w, 
                $this->screen_h, $this->os);
            return $ret;
        }
        return null;
    }
}
