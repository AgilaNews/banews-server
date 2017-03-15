<?php
/**
 * @file   BaseNewsSelecter.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Thu Jun 30 13:49:16 2016
 * 
 * @brief  
 * 
 * 
 */
use Phalcon\DI;
define('MIN_NEWS_SEND_COUNT', 6);
define('MAX_NEWS_SENT_COUNT', 8);
define('MORE_NEWS_FACTOR', 1.5);
define("DEFAULT_SAMPLING_DAY", 7);
define('CACHE_NEWS_FILTER', 'BS_NEWS_FILTER');

class BaseNewsSelector {
    public function __construct($controller, $channel_id) {
        $this->channel_id = $channel_id;
        $this->device_id = $controller->deviceId;
        $this->user_id = $controller->userSign;
        $this->client_version = $controller->client_version;
        $this->os = $controller->os;
        $this->di = $controller->di;
        $this->net = $controller->net;
        $this->screen_w = $controller->resolution_w;
        $this->screen_h = $controller->resolution_h;
    }

    protected function sampling($sample_count, $prefer){
        return $this->getPolicy()->sampling($this->channel_id, $this->device_id, $this->user_id,
                                            $sample_count, DEFAULT_SAMPLING_DAY, $prefer);
    }

    public function getPolicy() {
        if (!isset($this->policy)) {
            $this->policy = new ExpDecayListPolicy($this->di); 
        }
        return $this->policy;
    }

    
    public function getPolicyTag(){
        return "expdecay";
    }

    protected function newsFilter($newslist) {
        $ret = array();

        $cache = DI::getDefault()->get('cache');
        $key = CACHE_NEWS_FILTER;
        if ($cache && $cache->exists($key)) {
            foreach ($newslist as $newsid) {
                if (!($cache->sIsMember($key, $newsid))) {
                    $ret[] = $newsid;
                }
            }
        } else {
            $ret = $newslist;
        }
        return $ret;
    }

    protected function removeInvisible($models) {
        $ret = array();

        foreach ($models as $sign => $news_model) {
            if ($news_model && $news_model->is_visible == 1) {
                $ret[$sign] = $news_model;
            }
        }
        return $ret;
    }

    protected function removeDup($models) {
        $ret = array();
        $uniq = array();

        foreach ($models as $sign => $news_model) { 
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

    public function select($prefer) {
        /*
            get random number of news we want
            because we may get duplicated news because of content similariy, 
            so we query more news than we requried, then multiple the random number with a factor
            'MORE_NEWS_FACTOR'
        */
        $required = mt_rand(MIN_NEWS_SEND_COUNT, MAX_NEWS_SENT_COUNT);
        //I don't known if 1.5 is enough
        $base = round(MAX_NEWS_SENT_COUNT * MORE_NEWS_FACTOR);

        $selected_news_list = $this->sampling($base, $prefer); 
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
        $this->getPolicy()->setDeviceSent($this->device_id, $filter);
        return $ret;
    }

    protected function interveneAt(&$ret, $intervene, $pos) {
        if ($intervene instanceof BaseIntervene) {
            if ($intervene->isEmpty()) {
                return ;
            }
        }
        array_splice($ret, $pos, 0, array($intervene));
    }

    protected function insertAd(&$ret) {
        return;
        if (Features::Enabled(Features::AD_FEATURE, $this->client_version, $this->os) && count($ret) >= AD_INTERVENE_POS) {
            $abservice = DI::getDefault()->get('abtest');
            $t = $abservice->getTag("timeline_ad_position");
            $device_md5 = md5($this->device_id);

            $ad_intervene = new AdIntervene(array(
                                                  "type" => RenderLib::NEWS_LIST_TPL_AD_FB_MEDIUM,
                                                  "device" => $this->device_id,
                                                  ));

            if ($t == "forth_pos") {
                $pos = 3;
            } else if ($t == "six_pos") {
                $pos = 5;
            } else {
                $pos = AD_INTERVENE_POS;
            }
            if (count($ret) >= $pos) {
                $this->interveneAt($ret, $ad_intervene, $pos);
            }
        }
    }

    protected function setDeviceSeenToBF($keys) {
        if (RenderLib::isVideoChannel($this->channel_id)) {
            $filterName = BloomFilterService::FILTER_FOR_VIDEO;
        } else {
            switch ($this->channel_id) {
            case 10011:
                $filterName = BloomFilterService::FILTER_FOR_IMAGE;
                break;
            case 10012:
                $filterName = BloomFilterService::FILTER_FOR_GIF;
                break;
            default:
                return;
            }
        }
        
        $device_id = $this->device_id;
        $bf_service = $this->di->get("bloomfilter");
        $bf_service->add($filterName, 
                         array_map(
                                   function($key) use ($device_id){ 
                                       return $device_id . "_" . $key;
                                   }, $keys));
    }

    public static function getSelector($controller, $channel_id) {
        if ($channel_id == "10001") {
            return new HotSelector($controller, $channel_id);
        }
        if ($channel_id == "10013") {
            return new NbaSelector($controller, $channel_id);
        }

        /*
        if (in_array($channel_id, array("10002", "10010"))){
            return new SphinxSelector($controller, $channel_id);
        }
        */

        if (in_array($channel_id, array("10003", "10004", "10005", "10006", "10007", "10008", "10009"))) {
            return new PopularSelector($controller, $channel_id);
        }

        if (in_array($channel_id, array("10011", "10012", "10015"))) {
            return new RandomBackupSelector($controller, $channel_id);
        }

        if ((int)($channel_id / 10000) == 3) {
            return new VideoSelector($controller, $channel_id);
        }

        return new BaseNewsSelector($controller, $channel_id);
    }
}
