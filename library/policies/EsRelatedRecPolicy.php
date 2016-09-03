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
                $recNewsIdLst = json_decode($value, true); 
                if ($recNewsIdLst) {
                    $recNewsObjLst = News::batchGet($recNewsIdLst);
                    return $recNewsObjLst;
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
            //TODO @limeng
            //     remove this method by refractor code
            $myselfObj = News::getBySign($myself);
            $contentSignSet = array();
            if ($myselfObj->title) {
                $contentSignSet[$myselfObj->title] = true;
            }

            $resLst = array();
            $relatedNews = $this->esClient->search($searchParams);
            if (array_key_exists('hits', $relatedNews)) {
                if (array_key_exists('hits', $relatedNews['hits'])) {
                    foreach($relatedNews['hits']['hits'] as $curNews) {
                        if ($curNews['_score'] < $minThre) {
                            continue;
                        }

                        if (array_key_exists("title", $curNews["_source"]) &&
                            array_key_exists($curNews["_source"]["title"], $contentSignSet)) {
                            continue;
                        }

                        $contentSignSet[$curNews["_source"]["title"]] = true;

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
            #$this->logger->error(sprintf("[file:%s][line:%s][message:%s][code:%s]", 
            #    $e->getFile(), $e->getLine(), $e->getMessage(), $e->getCode()));
            return array();
        }
    }

    public function sampling($channel_id, $device_id, $user_id, $myself, 
        $pn=3, $day_till_now=7, array $options=null) {
        $resLst = $this->getRecommendNews($myself, $pn, 0);
        return $resLst;
    }
}

