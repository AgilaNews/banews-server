<?php

use Phalcon\DI;

define ('MAX_CLICK_COUNT', 1);
define ('REC_NEWS_SINGLE', 8);
define ('REC_NEWS_SPAN', 2);

class ClickRecommendPolicy extends BaseListPolicy {
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
                        $curChannelId = $curNews['_source']['channel'];
                        if (($curChannelId == '10011') || 
                            ($curChannelId == '10012')) {
                            continue;
                        }
                        $curArr = array("id" => $curNews["_id"], 
                            "score" => $curNews["_score"], 
                            "fetch_timestamp" => $curNews["_source"]["fetch_timestamp"]);
                        $resLst[] = $curArr;
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
        // since random selected seed news isn't significant, try sorted one
        //$seedClickedLst = $this->randomClick($clickedLst, MAX_CLICK_COUNT);
        $seedClickedLst = array_slice($clickedLst, 0, MAX_CLICK_COUNT);


        $recommendLst = array();
        foreach($seedClickedLst as $click) {
            $news_id = $click["id"];
            $resLst = $this->getRecommendNews($news_id, REC_NEWS_SINGLE, 0);
            foreach($resLst as $res) {
                $curTimestamp = intval($res['fetch_timestamp']);
                if (((time() - $curTimestamp) / 3600) >= REC_NEWS_SPAN) {
                            continue;
                }
                // get largest score of same news
                $key = array_search($res['id'], array_column($recommendLst, 'id'));
                if($key){
                    if($res['score'] > $recommendLst[$key]['score']){
                        $recommendLst[$key]['score'] = $res['score'];
                    }
                }else{
                    array_push($recommendLst, $res);
                }
            }
        }

        $recommendLst = $this->sentFilter($sentLst, $clickedLst, 
            $recommendLst);
        if (!$recommendLst) {
            return array();
        } else {
            // Half-Life Period = 8hour
            $recWeightLst = $this->genRecWeight($recommendLst, 8*3600);
            array_multisort($recWeightLst, SORT_DESC, SORT_NUMERIC, 
                $recommendLst);
            $retIdLst = array();
            foreach($recommendLst as $news){
                $id = $news['id'];
                $retIdLst[] = $id;
            }
            return array_slice($retIdLst, 0, min($pn, count($retIdLst)));        
        }
    }

    protected function sentFilter($sentNewsLst, $clickedLst, $newsLst) {
        $filterNewsLst = array();
        $clickedIdLst = array();
        foreach($clickedLst as $clickNews) {
            $clickedIdLst[] = $clickNews['id'];
        }
        foreach ($newsLst as $news) {
            $id = $news["id"];
            if (in_array($id, $sentNewsLst) || 
                in_array($id, $clickedIdLst) ||
                in_array($news, $filterNewsLst)) {
                continue;
            }
            $filterNewsLst[] = $news;
        }
        return $filterNewsLst;
    }

    protected function randomClick($clickLst, $numRandom) {
        shuffle($clickLst);
        return array_slice($clickLst, 0, 
            min(count($clickLst), $numRandom));
    }
    
    protected function genRecWeight($recLst, $singleSpan){
        $recWeightLst = array();
        foreach($recLst as $recNews){
            $score = $recNews['score'];
            $timestamp = $recNews["fetch_timestamp"];
            if(!$score or !$timestamp){
                $recWeightLst[] = 0.0;
            }
            if($timestamp >= time()) {
                $period = 0;
            }else{
                $period = floor((time()-$timestamp)/$singleSpan);
            }
            $weight = $score * pow(0.5, $period);
            $recWeightLst[] = $weight;
        }
        return $recWeightLst;
    } 
}
