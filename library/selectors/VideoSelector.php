<?php
use Phalcon\DI;
define('POPULAR_POLICY', 'popularRanking');
define('PERSONAL_INTEREST_POLICY', 'personalInterest');
class VideoSelector extends BaseNewsSelector {
    const MIN_NEWS_COUNT = 4;
    const MAX_NEWS_COUNT = 6;
    const LATELY_NEWS_COUNT = 2;

    public function getPolicyTag(){
        if ($this->channel_id != '30001'){
            return POPULAR_POLICY;
        }
        return 'personalInterest';
        $abService = $this->di->get('abtest');
        $tag = $abService->getTag("video_30001_strategy");
        if ($tag == "30001_personal_interest") {
            return PERSONAL_INTEREST_POLICY;
        }
        return POPULAR_POLLICY;
    }

    protected function getLatelyNewsCount(){
        return self::LATELY_NEWS_COUNT;
    }

    private function getPopularVideo($sample_count, $prefer, $options) {
        $popularPolicy = new PopularListPolicy($this->di);
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
        return $popularNewsLst;
    }

    private function getUserInterestVideo($sample_count, $prefer, $options) {
        $userInterestPolicy = new PersonalVideoInterestPolicy($this->di);
        $userInterestVideoCnt = max($sample_count - $this->getLatelyNewsCount(), 1);
        $userInterestVideoLst = $userInterestPolicy->sampling($this->channel_id,
            $this->device_id, $this->user_id, $userInterestVideoCnt,
            3, $prefer, $options);
        return $userInterestVideoLst;
    }

    public function sampling($sample_count, $prefer) {
        $policyNewsLst = array();
        $options = array();
        if ($prefer == "later") {
            $options["long_tail_weight"] = 1;
        }
        $policyTag = $this->getPolicyTag();
        if ($policyTag == POPULAR_POLICY) {
            $policyNewsLst = $this->getPopularVideo($sample_count, $prefer, $options);
        } elseif ($policyTag == PERSONAL_INTEREST_POLICY) {
            $policyNewsLst = $this->getUserInterestVideo($sample_count, $prefer, $options);
        }

        $randomPolicy = new VideoExpDecayListPolicy($this->di);
        $randomNewsLst = $randomPolicy->sampling($this->channel_id,
            $this->device_id, $this->user_id, self::MAX_NEWS_COUNT,
            3, $prefer, $options);

        foreach($randomNewsLst as $randomNews) {
            if (count($policyNewsLst) >= $sample_count) {
                break;
            }
            if (in_array($randomNews, $policyNewsLst)) {
                continue;
            }
            $policyNewsLst[] = $randomNews;
        }
        return $policyNewsLst;
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