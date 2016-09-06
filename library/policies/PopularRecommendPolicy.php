<?php

use Phalcon\DI;

define ('RECOMMEND_DAY_SPAN', 21);
define ('CLICK_DAY_SPAN', 4);

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

        $recommendLst = array();
        foreach($clickedLst as $click) {
            $news_id = $click["id"];
            $resLst = $this->getRecommendNews($news_id, 5, 0);
            foreach($resLst as $res) {
                array_push($recommendLst, $res); 
            }
        }

        $filterTimeNewsLst = $this->newsTimeFilter($recommendLst, RECOMMEND_DAY_SPAN);
        $filterSentNewsLst = $this->sentFilter($sentLst, $filterTimeNewsLst);
        if (!$filterSentNewsLst) {
            return array();
        } else {
            //random select news 
            shuffle($filterSentNewsLst);
           $retIdLst = array();
            foreach($filterSentNewsLst as $news){
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

        $tmpLst = array();
        foreach ($newsLst as $news) {
            $timestamp = $news['_source']["fetch_timestamp"];
            $tmpLst[] = $timestamp;
            if($timestamp>=$start and $timestamp<=$end){
                array_push($filterNewsLst, $news); 
                $tmpLst[] = $filterNewsLst['_id'];
            }
        }
        var_dump('++++++',$tmpLst);
        exit(0);
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

}
