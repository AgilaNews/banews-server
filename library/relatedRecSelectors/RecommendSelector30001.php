<?php

class RecommendSelector30001 extends BaseRecommendNewsSelector {
    public function __construct($channel_id, $controller) {
        parent::__construct($channel_id, $controller);
    }

    protected function getPolicy() {
        return new VideoRelatedRecPolicy($this->_di);
    }

    public function getPolicyTag(){
        return "VideoRelatedRecPolicy";
    }

    public function select($myself) {
        $ret = array();
        $cs = array(); //content sign
        $relatedPolicy = new VideoRelatedRecPolicy($this->_di); 
        $relatedNewsLst = $relatedPolicy->sampling($this->_channel_id, 
            $this->_device_id, $this->_user_id, $myself, 
            DEFAULT_RECOMMEND_NEWS_COUNT * 2);

        $models = array();
        if ($relatedNewsLst) {
            $models = News::batchGet($relatedNewsLst);
        }
        foreach ($models as $sign => $model) {
            if (!$model || ($sign == $myself) || 
                array_key_exists($model->content_sign, $cs)) {
                continue;
            }
            $ret[$sign]= $model;
            $cs[$model->content_sign] = $model; 

            if (count($ret) >= DEFAULT_RECOMMEND_NEWS_COUNT) {
                return $ret;
            }
        }

        $randomPolicy = new RandomRecommendPolicy($this->_di);
        $randomNewsLst = $randomPolicy->sampling($this->_channel_id, 
                                                 $this->_device_id, $this->_user_id, 
                                                 $myself, 
                                                 DEFAULT_RECOMMEND_NEWS_COUNT - count($ret) + 2);
        

        $randomModels = News::batchGet($randomNewsLst);
        foreach ($randomModels as $sign => $model) {
            if (!$model || ($sign == $myself) || 
                array_key_exists($model->content_sign, $cs)) {
                continue;
            }

            $cs[$model->content_sign] = $model; 
            $ret [$sign] = $model;
            if (count($ret) >= DEFAULT_RECOMMEND_NEWS_COUNT) {
                return $ret;
            }
        }

        return $ret;
    } 
}
