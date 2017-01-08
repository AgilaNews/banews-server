<?php
define ("DEFAULT_RECOMMEND_NEWS_COUNT", 3);

class BaseRecommendNewsSelector {
    public function __construct($channel_id, $controller) {
        $this->channel_id = $channel_id;
        $this->device_id = $controller->deviceId;
        $this->user_id = $controller->userSign;
        $this->client_version = $controller->client_version;
        $this->di = $controller->di;
    }

    protected function getPolicy() {
        return new EsRelatedRecPolicy($this->di);
    }

    public function getPolicyTag(){
        return "esRelatedRec";
    }

    protected function removeDup($models) {
        $ret = array();
        $uniq = array();

        foreach ($models as $sign => $news_model) { 
            if (!empty($news_model)) {
                continue;
            }
            if (array_key_exists($news_model->content_sign, $uniq) 
            ) {
                //content sign dup continue
                continue;
            }
            $ret [$sign] = $news_model;
            $uniq[$news_model->content_sign] = $news_model;
        }
        return $ret;
    }

    public function select($myself) {
        $esRelatedPolicy = new EsRelatedRecPolicy($this->_di); 
        $esRelatedNewsLst = $esRelatedPolicy->sampling(
            $this->_channel_id, 
            $this->_device_id, 
            $this->_user_id, 
            $myself, 
            DEFAULT_RECOMMEND_NEWS_COUNT * 2);
        array_unshift($esRelatedNewsLst, $myself);
        $models = $this->removeDup($esRelatedNewsLst);
        $models = News::batchGet($esRelatedNewsLst);
        if (count($models) < DEFAULT_RECOMMEND_NEWS_COUNT * 2) {
            $randomPolicy = new RandomRecommendPolicy($this->_di);
            $randomNewsLst = $randomPolicy->sampling(
                $this->_channel_id, 
                $this->_device_id, 
                $this->_user_id, 
                $myself, 
                DEFAULT_RECOMMEND_NEWS_COUNT * 2);
            $randomModels = News::batchGet($randomNewsLst);
            foreach ($randomModels as $sign => $model) {
                if (empty($model)) {
                    continue;
                }
                $models[$model->url_sign] = $model; 
            }
        }

        // post filter after ranking
        $models = array_values($models);
        $simhashFilter = new SimhashFilter($this->_di);
        $models = $simhashFilter->filtering($this->_channel_id,
            $this->_device_id, $models);
        $ret = array();
        foreach ($models as $newsObj) {
            if ($newsObj->url_sign == $myself) {
                continue;
            }
            if (count($ret) >= DEFAULT_RECOMMEND_NEWS_COUNT) {
                break;
            }
            $ret[$newsObj->url_sign] = $newsObj;
        }
        return $ret;
    } 
}
