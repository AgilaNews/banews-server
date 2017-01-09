<?php
/**
 * @file   DetailVideoRender.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Sat Jan  7 14:35:38 2017
 * 
 * @brief  
 * 
 * 
 */
class DetailVideoRender extends BaseDetailRender {
    public function render($news_model, $recommend_models = null) {
        $ret = parent::render($news_model, $recommend_models);

        $video = Video::getByNewsSign($news_model->url_sign);
        if ($video) {
            $ret["views"] = $video->view;
            $meta = json_decode($video->cover_meta, true);
            if ($meta &&
                is_numeric($meta["width"]) && 
                is_numeric($meta["height"])) {
                $ret["imgs"][] = RenderLib::LargeImageRender(LARGE_CHANNEL_IMG_PATTERN,
                                                             $this->c->net, $video->cover_image_sign,
                                                             $meta, $this->c->resolution_w, $this->c->resolution_h,
                                                             $this->c->os);
                $ret["videos"][] = RenderLib::VideoRender($video, $meta, $this->c->resolution_w, $this->c->resolution_h,
                                                          $this->c->os);
            }
        }
        return $ret;
    }
}
