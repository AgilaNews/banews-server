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

define("REDIS_HOT_WORD_KEY", "ALG_HOT_KEYWORDS_KEY");
class SearchController extends BaseController {

    public function HotwordsAction() {
        if (!$this->deviceId) {
            throw new HttpException(ERR_DEVICE_NON_EXISTS, "device id not found");
        }

        $size = $this->get_request_param("from", "int", false, 10);
        $cache = $this->di->get("cache");
        $hotwords = $cache->hGetAll(REDIS_HOT_WORD_KEY);
        arsort($hotwords);
        $cnt = 0;
        foreach ($hotwords as $key => $value) {
            if ($cnt >= $size){
                break;
            }
            $res [] = $key;
            $cnt += 1;
        }
        $this->setJsonResponse(array(
                                     "hotwords" => $res,
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

    private function emptyResponse($dispatch_id){
        $ret = array(
            "dispatch_id" => $dispatch_id,
            "news"=>array(),
        );
        $this->setJsonResponse($ret);
        return $this->response;

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
             if(!$model){
                unset($models[$urlsign]);
                continue;
            }
            $models[$urlsign]->title = $newslist[$urlsign];
        }
        return $models;
    } 

    public function IndexAction() {
        if (!$this->deviceId) {
            throw new HttpException(ERR_DEVICE_NON_EXISTS, "device id not found");
        }
        $channel_id = $this->get_request_param("channel_id", "int", true);
        $from = $this->get_request_param("from", "int", true);
        $size = $this->get_request_param("size", "int", true);
        $source = $this->get_request_param("source", "int", false, "searchbox");
        $words = $this->get_request_param("words", "string", true);
        $words = urldecode($words);
        $esClient = $this->di->get('elasticsearch');
        $percentage ="60%";
        $dispatch_id = substr(md5($words . $channel_id . $this->deviceId . time()), 16);
        if ($from + $size >= 200){
            return $this->emptyResponse($dispatch_id);
        }

        if($source=="hotwords"){
            $percentage ="100%";
        }

        $matchQuery = array(
            'match'=>array(
                'title'=>array(
                 "query"=>$words,
                 "minimum_should_match"=>$percentage,
                ),
            ),
        );
        $typeFilter = array('term'=>array('content_type'=>0));

        $now = time();
        //we only fetch recent 30 day news
        $timeLimit  = $now - ($now % 86400) - 86400 * 30;
        $timeFilter = array('range'=>array("post_timestamp"=>array("gte"=>$timeLimit)));
        $highlightPara = array(
                    'fields' => array('title'=>new \stdClass()),
                    'pre_tags' => array('<font>'),
                    'post_tags' => array('</font>'),
                );
        $searchParams = [
            'index' => 'banews',
            'type'  => 'article',
            'from' => $from,
            'size' => $size,
            'body' => [
                'query'=>[
                    'filtered' => [
                        'query' => $matchQuery,
                        'filter' => $typeFilter,
                        'filter' => $timeFilter,
                    ]
                ],
                'highlight' => $highlightPara,
            ]
        ]; 

        try {
            $searchResult = $esClient->search($searchParams);
        } catch(\Exception $e) {
            #$this->logger->error(sprintf("[file:%s][line:%s][message:%s][code:%s]", 
            #    $e->getFile(), $e->getLine(), $e->getMessage(), $e->getCode()));
            $searchResult =  array();
        }
        if (empty($searchResult)){
            return $this->emptyResponse($dispatch_id);
        }
        $models = $this->getNewsModel($searchResult);
        $render = new RenderSearch($this);
        $ret = array(
            "dispatch_id" => $dispatch_id,
            "news"=> $render->render($models),
        );
        $this->logEvent(EVENT_SEARCH_LIST, array(
                                                "dispatch_id"=>$dispatch_id,
                                                "news"=>array_keys($model),
                                                "channel_id"=>$channel_id,
                                                "from"=>$from,
                                                "size"=>$size,
                                                "words"=>$words,
                                                ));
        $this->setJsonResponse($ret);
        return $this->response;
    }
}


