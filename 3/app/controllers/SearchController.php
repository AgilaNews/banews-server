<?php
/**
 * @file   SearchController.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Fri Dec  9 11:00:50 2016
 * 
 * @brief  
 * 
 * 
 */
class SearchController extends BaseController {

    public function __construct($di) {
        parent::__construct($di);
        $this->esClient = $di->get('elasticsearch');
        $this->logger = $di->get('logger');
    }

    public function HotwordsAction() {
        $this->setJsonResponse(array(
                                     "hotwords" => array("lian", "zhan", "animal"),
                                     ));
        return $this->response;
    }

    public function IndexAction() {
        $channel_id = $this->get_request_param("channel_id", "int", true);
        $from = $this->get_request_param("from", "int", true);
        $size = $this->get_request_param("size", "int", true);
        $words = $this->get_request_param("words", "string", true);

        $searchParams = array(
            'index' => 'banews-article',
            'type'  => 'article',
            'body'  => array(
                'query' => array(
                    'match' => array('title', $words),
                ),
                'highlight' => array(
                    'fields' => array('content', array())
                ),
            ),
        );
        try {
            $resLst = array();
            $relatedNews = $this->esClient->search($searchParams);
            return $resLst;
        } catch(\Exception $e) {
            #$this->logger->error(sprintf("[file:%s][line:%s][message:%s][code:%s]", 
            #    $e->getFile(), $e->getLine(), $e->getMessage(), $e->getCode()));
            return array();
        }
    }
}


