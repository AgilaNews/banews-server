<?php
/**
 * 
 * @file    Render30001.php
 * @authors Zhao Yulong (elysium.zyl@gmail.com)
 * @date    2016-10-27 21:35:42
 * @version $Id$
 */

class VideoRecommendRender extends BaseRecommendRender {
    public function render($models) {
        $ret = array();
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
        RenderLib::FillTpl($ret, $this->placement_id, RenderLib::PLACEMENT_RECOMMEND);
        return $ret;
    }

    protected function serializeNewsCell($news_model) {
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

            $ret["imgs"][] = RenderLib::LargeImageRender(LARGE_CHANNEL_IMG_PATTERN,
                                                         $this->net, $video->cover_image_sign,
                                                         $meta, $this->screen_w, $this->screen_h, $this->os);
            $ret["videos"][] = RenderLib::VideoRender($video, $meta, $this->screen_w, 
                                                      $this->screen_h, $this->os);
            return $ret;
        }

        return null;
    }
}
