<?php
define ("DEFAULT_RECOMMEND_NEWS_COUNT", 3);

class BaseRecommendNewsSelector {
    public function __construct($channel_id, $device_id, $user_id, $di) {
        $this->_channel_id = $channel_id;
        $this->_device_id = $device_id;
        $this->_user_id = $user_id;
        $this->_di = $di;
    }

    protected function getPolicy() {
        return new EsRelatedRecPolicy($this->_di);
    }

    public function getPolicyTag(){
        return "esRelatedRec";
    }

    public function select($myself) {
        $ret = array();
        $cs = array(); //content sign
        $esRelatedPolicy = new EsRelatedRecPolicy($this->_di); 
        $esRelatedNewsLst = $esRelatedPolicy->sampling($this->_channel_id, 
            $this->_device_id, $this->_user_id, $myself, 
            DEFAULT_RECOMMEND_NEWS_COUNT * 2);

        $models = News::batchGet($esRelatedNewsLst);
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
        foreach ($randomModels as $sign => $models) {
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
