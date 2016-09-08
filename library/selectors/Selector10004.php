<?php

define('MIN_NEWS_COUNT', 8);
define('MAX_NEWS_COUNT', 10);
define("LATELY_NEWS_COUNT", 2);

class Selector10004 extends BaseNewsSelector {

    public function getPolicyTag(){
        return 'popularRanking';
    }

    public function sampling($sample_count, $prefer) {
        $randomPolicy = new ExpDecayListPolicy($this->_di);
        $popularPolicy = new PopularListPolicy($this->_di);
        $options = array();
        if ($prefer == "later") {
            $options["long_tail_weight"] = 0;
        }
        $popularNewsCnt = max($sample_count - LATELY_NEWS_COUNT, 1);
        $popularNewsLst = $popularPolicy->sampling($this->_channel_id, 
            $this->_device_id, $this->_user_id, $popularNewsCnt, 
            3, $prefer, $options);
        $randomNewsLst = $randomPolicy->sampling($this->_channel_id, 
            $this->_device_id, $this->_user_id, MAX_NEWS_COUNT, 
            3, $prefer, $options);

        foreach($randomNewsLst as $randomNews) {
            if (count($popularNewsCnt) >= $sample_count) {
                break;
            }
            if (in_array($randomNews, $popularNewsLst)) {
                continue;
            }
            $popularNewsLst[] = $randomNews;
        }
        return $popularNewsLst;
    }

    public function select($prefer) {
        $required = mt_rand(MIN_NEWS_COUNT, MAX_NEWS_COUNT);
        $selected_news_list = $this->sampling($required, $prefer);
        $models = News::BatchGet($selected_news_list);
        $models = $this->removeInvisible($models);
        $models = $this->removeDup($models);
        if (count($models) > $required) {
            $models = array_slice($models, 0, $required);
        }

        $this->getPolicy()->setDeviceSent($this->_device_id, array_keys($models));
        return $models;
    }
}
