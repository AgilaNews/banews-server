<?php

use Phalcon\DI;

define ('RECOMMEND_DAY_SPAN', 21);
define ('CLICK_DAY_SPAN', 4);
define ('MAX_CLICK_COUNT', 5);
define ('REC_NEWS_SINGLE', 5);

class PopularRecommendPolicy extends BaseListPolicy {
    public function __construct($di) {
        parent::__construct($di);
        $this->esClient = $di->get('elasticsearch');
    }

    protected static function _getRecFromCache($sign) {
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            $key = CACHE_NEWS_RECOMMEND_PREFIX . $sign;
            $value = $cache->get($key);
            if ($value) {
                $recNewsIdLst = json_decode($value, true); 
                if ($recNewsIdLst) {
                    return $recNewsIdLst;
                }
            }
        }
        return array();
    }

    protected static function _saveRecToCache($newsSign, $recNewsObjLst){
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            $key = CACHE_NEWS_RECOMMEND_PREFIX . $newsSign;
            $cache->multi();
            $newsSignLst = array();
            foreach ($recNewsObjLst as $curSign) {
                $newsSignLst[] = $curSign;
            }

            $cache->set($key, json_encode($newsSignLst, true));
            $cache->expire($key, CACHE_NEWS_RECOMMEND_TTL);
            $cache->exec();
        }
    }

    protected function getRecommendNews($myself, $pn, $minThre=0.) {
        $recNewsLst = self::_getRecFromCache($myself);
        if ($recNewsLst) {
            return $recNewsLst;
        }

        $searchParams = array(
            'index' => 'banews-article',
            'type' => 'article',
            'body' => array(
                'query' => array(
                    'more_like_this' => array(
                        'fields' => array('title', 'plain_text'),
                        'like' => array(
                            '_index' => 'banews-article',
                            '_type' => 'article',
                            '_id' => $myself,
                        ),
                        'max_query_terms' => 30,
                        'min_term_freq' => 1,
                        'min_doc_freq' => 1,
                    ),
                ),
            ),
        );
        try {
            $resLst = array();
            $relatedNews = $this->esClient->search($searchParams);
            if (array_key_exists('hits', $relatedNews)) {
                if (array_key_exists('hits', $relatedNews['hits'])) {
                    foreach($relatedNews['hits']['hits'] as $curNews) {
                        if ($curNews['_score'] < $minThre) {
                            continue;
                        }
                        array_push($resLst, $curNews); 
                        if (count($resLst) > $pn)
                            break;
                    }
                }
            }

            if ($resLst) {
                self::_saveRecToCache($myself, $resLst);
            }
            return $resLst;
        } catch(\Exception $e) {
            #$this->logger->error(sprintf("[file:%s][line:%s][message:%s][code:%s]", 
            #    $e->getFile(), $e->getLine(), $e->getMessage(), $e->getCode()));
            return array();
        }
    }

    public function sampling($channel_id, $device_id, $user_id, $pn, 
        $day_till_now, $prefer, array $options = array()) {
        $sentLst = $this->_cache->getDeviceSeen($device_id);
        $clickedLst = $this->_cache->getDeviceClick($device_id);
        if (count($clickedLst)==0){
            return array();
        }
        $clickedLst = $this->randomClick($clickedLst, MAX_CLICK_COUNT);

        $recommendLst = array();
        foreach($clickedLst as $click) {
            $news_id = $click["id"];
            $resLst = $this->getRecommendNews($news_id, REC_NEWS_SINGLE, 0);
            foreach($resLst as $res) {
                array_push($recommendLst, $res); 
            }
        }

        //$recommendLst = $this->newsTimeFilter($recommendLst, RECOMMEND_DAY_SPAN);
        $recommendLst = $this->sentFilter($sentLst, $recommendLst);

        if (!$recommendLst) {
            return array();
        } else {
            $recWeightLst = $this->genRecWeight($recommendLst, 8*3600);//Half-Life Period = 8hour
            array_multisort($recWeightLst,SORT_DESC, SORT_NUMERIC, $recommendLst);
            $retIdLst = array();
            foreach($recommendLst as $news){
                $id = $news['_id'];
                $retIdLst[] = $id;
            }
            return array_slice($retIdLst, 0, $pn);        
        }

    }

    protected function sentFilter($sentNewsLst, $newsLst) {
        $filterNewsLst = array();
        foreach ($newsLst as $news) {
            $id = $news["_id"];
            if (!in_array($id, $sentNewsLst)) {
                array_push($filterNewsLst, $news); 
            }
        }
        return $filterNewsLst;
    }

    protected function newsTimeFilter($newsLst, $timeSpan) {
        $filterNewsLst = array();
        $now = time();
        $start = ($now - ($timeSpan * 86400));
        $start = $start - ($start % 86400);
        $end = ($now + 86400) - (($now + 86400) % 86400);

        foreach ($newsLst as $news) {
            $timestamp = $news['_source']["fetch_timestamp"];
            if($timestamp>=$start and $timestamp<=$end){
                array_push($filterNewsLst, $news); 
            }
        }
        return $filterNewsLst;
    }

    protected function actionTimeFilter($actionLst, $timeSpan) {
        $filterActionLst = array();
        $now = time();
        $start = ($now - ($timeSpan * 86400));
        $start = $start - ($start % 86400);
        $end = ($now + 86400) - (($now + 86400) % 86400);

        foreach ($actionLst as $action) {
            $timestamp = $action['time'];
            if($timestamp>=$start and $timestamp<=$end){
                array_push($filterActionLst, $action); 
            }
        }
        return $filterActionLst;
    }

    protected function randomClick($clickLst, $numRandom) {
        $total = count($clickLst);
        if($numRandom>$total) return $clickLst;
        else{
            shuffle($clickLst);
            return array_slice($clickLst,0,$numRandom);
        }
        
    }
    
    protected function genRecWeight($recLst, $singleSpan){
        $recWeightLst = array();
        foreach($recLst as $recNews){
            $score = $recNews['_score'];
            $timestamp = $recNews['_source']["fetch_timestamp"];
            if(!$score or !$timestamp){
                $recWeightLst[] = 0.0;
            }
            if($timestamp>=time()) {
                $period = 0;
            }else{
                $period = floor((time()-$timestamp)/$singleSpan);
            }
            $weight = $score * pow(0.5, $period);
            $recWeightLst[] = $weight;
        }
        return $recWeightLst;
    } 

    public function sort2d($array, $key, $sord_order=SORT_ASC, $sort_type=SORT_NUMERIC){
        if(is_array($array)){
            foreach($array as $arr){
                if(is_array($arr)){
                    $key_arr[] = $arr[$key];
                }else return false;
            }
        }else return false;
        array_multisort($key_arr, $sort_order, $sort_type, $array);
        return $array;
    }
}
