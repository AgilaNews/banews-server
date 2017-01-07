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
        }

        return $ret;
    }
}