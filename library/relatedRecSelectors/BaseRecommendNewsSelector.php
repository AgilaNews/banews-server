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
        return new RandomRecommendPolicy($this->_di);
    }

    public function getPolicyTag(){
        return "random";
    }

    public function select($myself) {
        $policy = $this->getPolicy();
        $base = DEFAULT_RECOMMEND_NEWS_COUNT + 1;
        $news_list = $policy->sampling($this->_channel_id, $this->_device_id, 
            $this->_user_id, $myself, $base);
        $models = News::batchGet($news_list);

        if (array_key_exists($myself, $models)) {
            //do not recommend myself
            unset($models[$myself]);
        }

        return array_slice($models, 0, DEFAULT_RECOMMEND_NEWS_COUNT);
    } 
}
