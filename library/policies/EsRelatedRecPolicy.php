<?php

use Elasticsearch\ClientBuilder;
use Phalcon\DI;

class EsRelatedRecPolicy extends BaseRecommendPolicy {
    public function __construct($di) {
        parent::__construct($di);
        $clientBuilder = ClientBuilder::create();
        $hosts = ['http://10.8.18.130:9200'];
        $clientBuilder->setHosts($hosts);
        $this->esClient = $clientBuilder->build();
        $this->logger = DI::getDefault()->get('logger');
    }

    protected function moreLikeThis($myself) {
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
            $relatedNews = $this->esClient->search($searchParams);
            foreach($relatedNews as $curNews) {
                var_dump($relatedNews);
            }
            exit(0);
        } catch(\Exception $e) {
            $this->logger.error(sprintf("[file:%s][line:%s][message:%s][code:%s]", 
                $e->getFile(), $e->getLine(), $e->getMessage(), $e->getCode()));
        }
    }

    public function sampling($channel_id, $device_id, $user_id, $myself, 
        $pn=3, $day_till_now=7, array $options=null) {
        $this->moreLikeThis($myself);
    }
}

