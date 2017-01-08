<?php
/**
 * @file   DetailPhotoRender.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Sat Jan  7 14:35:38 2017
 * 
 * @brief  
 * 
 * 
 */
class DetailPhotoRender extends BaseDetailRender {
    public function render($news_model, $recommend_models = null) {
        $ret = parent::render($news_model, $recommend_models);
        
        $ret["views"] = $news_model->liked * 3 + mt_rand() % 100;
        return $ret;
    }
}