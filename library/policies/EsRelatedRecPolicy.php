<?php

use Phalcon\DI;
define("TYPE_PLAIN_NEWS", 0);
define("TYPE_IMAGE_NEWS", 1);
define("TYPE_GIF_NEWS", 2);
define("TYPE_VIDEO_NEWS", 3);

class EsRelatedRecPolicy extends BaseRecommendPolicy {
    public function __construct($di) {
        parent::__construct($di);
        $this->esClient = $di->get('elasticsearch');
        $this->logger = $di->get('logger');
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

    protected function getContentType($channel_id){
        if (RenderLib::isVideoChannel($channel_id)){
            return TYPE_VIDEO_NEWS;
        }
        if (RenderLib::isPhotoChannel($channel_id)){
            return TYPE_IMAGE_NEWS;
        }
        if (RenderLib::isGifChannel($channel_id)){
            return TYPE_GIF_NEWS;
        }
        return TYPE_PLAIN_NEWS;
    }

    protected function genSearchParams($myself, $channel_id){
        $contentType = $this->getContentType($channel_id);
        $typeFilter = array('term'=>array('content_type'=>$contentType));
        $queryFilter = array("bool"=>array("must"=>array($typeFilter)));
        $moreLikeThisQuery = array(
                    'more_like_this' => array(
                        'fields' => array('title', 'plain_text'),
                        'like' => array(
                            '_index' => 'banews',
                            '_type' => 'article',
                            '_id' => $myself,
                        ),
                        'max_query_terms' => 30,
                        'min_term_freq' => 1,
                        'min_doc_freq' => 1,
                    ),
                );
        $query =
            [
                'filtered' => [
                    'query' => $moreLikeThisQuery,
                    'filter' => $queryFilter,
                ]
            ];

        $searchParams =
            [
                'index' => 'banews',
                'type'  => 'article',
                'body' => [
                    "query"=>$query,
                ]
            ];

        return $searchParams;
    } 

    protected function getRecommendNews($myself, $channel_id, $pn, $minThre=0.) {
        $recNewsLst = self::_getRecFromCache($myself);
        if ($recNewsLst) {
            return $recNewsLst;
        }

        $searchParams = $this->genSearchParams($myself, $channel_id);
        try {
            $resLst = array();
            $relatedNews = $this->esClient->search($searchParams);
            if (array_key_exists('hits', $relatedNews)) {
                if (array_key_exists('hits', $relatedNews['hits'])) {
                    foreach($relatedNews['hits']['hits'] as $curNews) {
                        if ($curNews['_score'] < $minThre) {
                            continue;
                        }
                        $curArr = array("id" => $curNews["_id"], 
                            "score" => $curNews["_score"], 
                            "fetch_timestamp" => $curNews["_source"]["fetch_timestamp"]/1000);
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

    public function sampling($channel_id, $device_id, $user_id, $myself, 
        $pn=3, $day_till_now=7, array $options=null) {
        $resLst = $this->getRecommendNews($myself, $channel_id, $pn, 0);
        $resIdLst = array();
        foreach($resLst as $curRes) {
            $resIdLst[] = $curRes['id'];
        }
        return $resIdLst;
    }
}

