<?php

define('MIN_NEWS_COUNT', 8);
define('MAX_NEWS_COUNT', 10);
define("LATELY_NEWS_COUNT", 2);

class Selector10004 extends BaseNewsSelector {
    public function getPolicyTag(){
        return 'popularRanking';
    }

    protected function getLatelyNewsCount(){
        return LATELY_NEWS_COUNT;
    } 

    public function sampling($sample_count, $prefer) {
        $randomPolicy = new ExpDecayListPolicy($this->_di);
        $popularPolicy = new PopularListPolicy($this->_di);
        $options = array();
        if ($prefer == "later") {
            $options["long_tail_weight"] = 0;
        }
        $popularNewsCnt = max($sample_count - $this->getLatelyNewsCount(), 1);
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

        $ret = array();
        $filter = array();
        for ($i = 0; $i < count($selected_news_list); $i++) {
            if (array_key_exists($selected_news_list[$i], $models)) {
                $ret []= $models[$selected_news_list[$i]];
                $filter []= $models[$selected_news_list[$i]]->url_sign;
                if (count($ret) >= $required) {
                    break;
                }
            }
        }
        
        $this->insertAd($ret);        
        $this->getPolicy()->setDeviceSent($this->_device_id, $filter);
        return $ret;
    }
}
