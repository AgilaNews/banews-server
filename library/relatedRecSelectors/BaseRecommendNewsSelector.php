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
        $base = DEFAULT_RECOMMEND_NEWS_COUNT + 1;
        $esRelatedPolicy = new EsRelatedRecPolicy($this->_di); 
        $esRelatedNewsLst = $esRelatedPolicy->sampling($this->_channel_id, 
            $this->_device_id, $this->_user_id, $myself, $base);

        if (count($esRelatedNewsLst) < $base) {
            $randomPolicy = new RandomRecommendPolicy($this->_di);
            $randomNewsLst = $randomPolicy->sampling($this->_channel_id, 
                $this->_device_id, $this->_user_id, $myself, $base);
            foreach ($randomNewsLst as $curNews) {
                if (count($esRelatedNewsLst) >= $base) {
                    break;
                }
                if (in_array($curNews, $esRelatedNewsLst)) {
                    continue;
                }
                $esRelatedNewsLst[] = $curNews; 
            }
        }

        $models = News::batchGet($esRelatedNewsLst);
        $ret = array();
        if (array_key_exists($myself, $models)) {
            //do not recommend myself
            unset($models[$myself]);
        }
        foreach ($models as $model) {
            if ($model) {
                $ret []= $model;
            }
            if (count($ret) >= DEFAULT_RECOMMEND_NEWS_COUNT) {
                break;
            }
        }

        return $ret;
    } 
}
