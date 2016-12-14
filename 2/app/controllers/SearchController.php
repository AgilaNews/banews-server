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

    public function HotwordsAction() {
        $this->setJsonResponse(array(
                                     "hotwords" => array("lian", "zhan", "animal"),
                                     ));
        return $this->response;
    }

    private function checkValid($searchResult){
        $channel_id = $searchResult["_source"]["channel"];
        if($channel_id == "10011" or $channel_id == "10012" or $channel_id == "30001"){
            return FALSE;
        }
        return TRUE;
    }

    public function getNewsModel($searchResult){
        $newslist = array();
        foreach($searchResult['hits']['hits'] as $result) {
            if (!$this->checkValid($result)){
                continue;
            } 
            $sign = $result["_source"]["id"];
            $highlight = $result["highlight"]["title"][0];
            $newslist[$sign] = $highlight;
        }
        $newskey = array_keys($newslist);
        $models = News::batchGet($newskey);
        foreach ($models as $urlsign=>$model){
            $models[$urlsign]->title = $newslist[$urlsign];
        }
        return $models;
    } 

    public function IndexAction() {
        $channel_id = $this->get_request_param("channel_id", "int", true);
        $from = $this->get_request_param("from", "int", true);
        $size = $this->get_request_param("size", "int", true);
        $words = $this->get_request_param("words", "string", true);
        $words = urldecode($words);
        $esClient = $this->di->get('elasticsearch');

       $searchParams = array(
            'index' => 'banews-article',
            'type'  => 'article',
            'from' => $from,
            'size' => $size,
            'body'  => array(
                'query' => array(
                    'match' => array('title'=>$words),
                ),
                'highlight' => array(
                    'fields' => array('title'=>new \stdClass()),
                    'pre_tags' => array('<font>'),
                    'post_tags' => array('</font>'),
                ),
            ),
        ); 
        try {
            $searchResult = $esClient->search($searchParams);
            $resLst = array();
        } catch(\Exception $e) {
            #$this->logger->error(sprintf("[file:%s][line:%s][message:%s][code:%s]", 
            #    $e->getFile(), $e->getLine(), $e->getMessage(), $e->getCode()));
            $searchResult =  array();
        }
        $models = $this->getNewsModel($searchResult);
        $render = new RenderSearch($this);
        $dispatch_id = substr(md5($words . $channel_id . $this->deviceId . time()), 16);
        $ret = array(
            "dispatch_id" => $dispatch_id,
            "news"=> $render->render($models),
        );
        $this->setJsonResponse($ret);
        return $this->response;
    }
}


