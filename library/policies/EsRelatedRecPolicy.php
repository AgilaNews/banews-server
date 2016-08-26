<?php

use Phalcon\DI;

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
                $recNewsIdLst = explode(",", $value); 
                if ($recNewsIdLst) {
                    $recNewsObjLst = News::batchGet($recNewsIdLst);
                    return $recNewsObjLst;
                }
            }
        }
        return array();
    }

    protected static function _saveRecToCache($newsId, $recNewsObjLst){
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            $key = CACHE_NEWS_RECOMMEND_PREFIX . $model->url_sign;
            $cache->multi();
            $newsIdLst = array();
            foreach ($recNewsObjLst as $curNewsObj) {
                $newsIdLst[] = $curNewsObj->url_sign;
            }
            $cache->set($key, implode(",", $newsIdLst));
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
            $myselfObj = News::get($myself);
            $contentSignSet = array();
            if ($myselfObj->content_sign) {
                $contentSignSet[] = $myselfObj->content_sign;
            }
            $resLst = array();
            $relatedNews = $this->esClient->search($searchParams);
            if (array_key_exists('hits', $relatedNews)) {
                if (array_key_exists('hits', $relatedNews['hits'])) {
                    foreach($relatedNews['hits']['hits'] as $curNews) {
                        if ($curNews['_score'] < $minThre)
                            continue;
                        if (in_array($curNews->content_sign, $contentSignSet)) {
                            continue;
                        }
                        $contentSignSet[] = $curNews->content_sign;
                        $resLst[] = $curNews["_id"];
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
            $this->logger->error(sprintf("[file:%s][line:%s][message:%s][code:%s]", 
                $e->getFile(), $e->getLine(), $e->getMessage(), $e->getCode()));
            return array();
        }
    }

    public function sampling($channel_id, $device_id, $user_id, $myself, 
        $pn=3, $day_till_now=7, array $options=null) {
        $resLst = $this->getRecommendNews($myself, $pn, 0);
        return $resLst;
    }
}

