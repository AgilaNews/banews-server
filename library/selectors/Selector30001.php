<?php
use Phalcon\DI;
class Selector30001 extends BaseNewsSelector {
    const MIN_NEWS_COUNT = 4;
    const MAX_NEWS_COUNT = 6;
    const LATELY_NEWS_COUNT = 2;

    public function getPolicyTag(){
        return 'popularRanking';
    }

    protected function getLatelyNewsCount(){
        return self::LATELY_NEWS_COUNT;
    } 

    public function sampling($sample_count, $prefer) {
        $randomPolicy = new RandomListPolicy($this->di);
        $popularPolicy = new PopularListPolicy($this->di);
        $options = array();
        if ($prefer == "later") {
            $options["long_tail_weight"] = 1;
        }
        $popularNewsCnt = max($sample_count - $this->getLatelyNewsCount(), 1);
        $popularNewsLst = $popularPolicy->sampling($this->channel_id, 
            $this->device_id, $this->user_id, $popularNewsCnt, 
            3, $prefer, $options);

        //* hack for oppo phone
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            $key = sprintf(OPPO_DEVICE_KEY, $this->device_id);
            if($cache->exists($key)) {
                $popularNewsLst = array();
                $popularNewsCnt = 0;
            }
        }
        //*/

        if (count($popularNewsLst) >= $sample_count) {
            return $popularNewsLst;
        }

        $abservice = DI::getDefault()->get('abtest');
        $t = $abservice->getTag("video_random_policy");

        if ($t == "video_exp") {
            $randomPolicy = new VideoExpDecayListPolicy($this->di);
        }

        $randomNewsLst = $randomPolicy->sampling($this->channel_id, 
            $this->device_id, $this->user_id, self::MAX_NEWS_COUNT, 
            3, $prefer, $options);
        
        foreach($randomNewsLst as $randomNews) {
            if (count($popularNewsLst) >= $sample_count) {
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
        $required = mt_rand(self::MIN_NEWS_COUNT, self::MAX_NEWS_COUNT);
        return $this->selectWithCount($prefer, $required);
    }

    public function selectWithCount($prefer, $count) {
        $selected_news_list = $this->sampling($count, $prefer);
        $selected_news_list = array_unique($selected_news_list);
        $models = News::BatchGet($selected_news_list);
        $models = $this->removeInvisible($models);

        $ret = array();
        $filter = array();
        for ($i = 0; $i < count($selected_news_list); $i++) {
            if (array_key_exists($selected_news_list[$i], $models)) {
                $ret []= $models[$selected_news_list[$i]];
                $filter []= $models[$selected_news_list[$i]]->url_sign;
                if (count($ret) >= $count) {
                    break;
                }
            }
        }

        $this->setDeviceSeenToBF($filter);
        return $ret;
    }
}
