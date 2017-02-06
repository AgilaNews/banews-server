<?php
/**
 * 
 * @file    NewsController.php
 * @authors Zhao Yulong (elysium.zyl@gmail.com)
 * @date    2016-10-27 17:15:30
 * @version $Id$
 */

use Phalcon\Mvc\Model\Query;

class NewsController extends BaseController {
    private $featureChannelLst = array(10001);
    public function DetailAction() {
        if (!$this->request->isGet()){
            throw new HttpException(ERR_INVALID_METHOD,
                "read news must be get");
        }
        $cache = $this->di->get("cache");
        if (!$cache) {
            throw new HttpException(ERR_INTERNAL_DB, "cache error");
        }        

        $newsSign = $this->get_request_param("news_id", "string", true);
        $news_model = News::getBySign($newsSign);
        if (!$news_model) {
            throw new HttpException(ERR_NEWS_NON_EXISTS, "news not found");
        }
        $redis = new NewsRedis($cache);
        $redis->setDeviceClick($this->deviceId, $newsSign, time());
        $this->incrViewCount($newsSign);

        $render = BaseDetailRender::getRender($this, $news_model->channel_id);
        $ret = $render->render($news_model, null);

        $this->logEvent(EVENT_NEWS_DETAIL, array(
                                                 "news_id"=> $newsSign,
                                                 "ad" => $ret["ad"],
                                                 ));
        
        $this->logger->info(sprintf("[Detail][news:%s][imgs:%d][channel:%d][recommend:%d]", $newsSign, count($ret["imgs"]),
                                     $news_model->channel_id, count($ret["recommend_news"])));

        News::saveActionToCache($newsSign, 
                                CACHE_FEATURE_CLICK_PREFIX,
                                CACHE_FEATURE_CLICK_TTL);
        $this->setJsonResponse($ret);
        return $this->response;
    }

    
    public function ListAction() {
        if (!$this->request->isGet()) {
            throw new HttpException(ERR_INVALID_METHOD, "not supported method");
        }
        if (!$this->deviceId) {
            throw new HttpException(ERR_DEVICE_NON_EXISTS, "device-id not found");
        }

        $channel_id = $this->get_request_param("channel_id", "int", true);
        $prefer = $this->get_request_param('dir', "string", false, "later");
        $dispatch_ids = array();
        if (!($prefer == 'later' || $prefer == 'older')) {
            throw new HttpException(ERR_BODY, "'dir' error");
        }

        $selector = BaseNewsSelector::getSelector($this, $channel_id);

        $newsFeatureDct = array();
        if (in_array($channel_id, $this->featureChannelLst)) {
            list($dispatch_models, $newsFeatureDct) = 
                $selector->select($prefer);
        } else {
            $dispatch_models = $selector->select($prefer);
        }

        $dispatch_ids = array();
        foreach ($dispatch_models as $dispatch_model) {
            if (isset($dispatch_model->url_sign)) {
                $dispatch_ids []= $dispatch_model->url_sign;
            }
        }
        News::batchSaveActionToCache($dispatch_ids, 
                                     CACHE_FEATURE_DISPLAY_PREFIX, 
                                     CACHE_FEATURE_DISPLAY_TTL);

        $render = BaseListRender::getRender($this, $channel_id);

        $dispatch_id = substr(md5($prefer . $channel_id . $this->deviceId . time()), 16);
        if (Features::Enabled(Features::AB_FLAG_FEATURE, $this->client_version, $this->os)) {
            $ret = array(
                "dispatch_id" => $dispatch_id,
                "news" => $render->render($dispatch_models),
                "abflag" => json_encode($this->abflags),
            );
        } else { 
            $ret[$dispatch_id] = $render->render($dispatch_models);
        }

        $this->logger->info(sprintf("[List][dispatch_id:%s][policy:%s][pfer:%s][cnl:%d][sent:%d]",
                                    $dispatch_id, $selector->getPolicyTag(), $prefer, 
                                    $channel_id, count($dispatch_models)));

        $this->logEvent(EVENT_NEWS_LIST, array(
                                              "dispatch_id"=> $dispatch_id,
                                              "news"=> $dispatch_ids,
                                              "policy"=> $selector->getPolicyTag(),
                                              "channel_id" => $channel_id,
                                              "prefer" => $prefer,
                                              ));
        if (in_array($channel_id, $this->featureChannelLst)) {
            foreach ($dispatch_ids as $newsId) {
                if (array_key_exists($newsId, $newsFeatureDct)) {
                    $param = array();
                    $param['news_id'] = $newsId;
                    $param['features'] = json_encode($newsFeatureDct[$newsId]);
                    $this->logFeature($dispatch_id, $param);
                }
            }
        }
        $this->setJsonResponse($ret);
        return $this->response;
    }

    
    public function LikeAction() {
        if (!$this->request->isPost()) {
            throw new HttpException(ERR_INVALID_METHOD, "not supported method");
        } 

        $req = $this->request->getJsonRawBody(true);
        if (null === $req) {
            throw new HttpException(ERR_BODY, "body format error");
        }

        $newsSign = $this->get_or_fail($req, "news_id", "string");
        $now = News::getBySign($newsSign);
        if (!$now) {
            throw new HttpException(ERR_NEWS_NON_EXISTS, "news $newsSign non exists");
        }

        if (!isset($now->content_sign) || $now->content_sign == null || count($now->content_sign) == 0) {
            $now->content_sign = "";
        } 

        
        $originLike = $now->liked;
        $pseudoLike = mt_rand(1, 10);
        if ($pseudoLike == 1) {
            $now->liked += 2;
            $this->logger->info(sprintf("[pseudo:%d]", $now->liked));
        } else {
            $now->liked += 1;
        }

        $ret = $now->save();
        if (!$ret) {
            $this->logger->warning(sprintf("save error: %s", join(",",$now->getMessages())));
            throw new HttpException(ERR_INTERNAL_DB, "internal error");
        }

        $ret = array (
            "message" => "ok",
            "liked" => $originLike + 1,
        );

        $this->logger->info(sprintf("[Like][liked:%s]", $ret["liked"]));
        $this->logEvent(EVENT_NEWS_LIKE, array("news_id"=>$newsSign, "liked"=>$ret["liked"]));
        $this->setJsonResponse($ret);
        return $this->response;

    }

    public function RecommendAction() {
        if (!$this->request->isGet()){
            throw new HttpException(ERR_INVALID_METHOD,
                "read news must be get");
        }
        if (!$this->deviceId) {
            throw new HttpException(ERR_DEVICE_NON_EXISTS, "device-id not found");
        }

        $newsSign = $this->get_request_param("news_id", "string", true);
        $news_model = News::getBySign($newsSign);
        if (!$news_model) {
            throw new HttpException(ERR_NEWS_NON_EXISTS, "news not found");
        }

        $channel_id = $news_model->channel_id;
        $recommend_selector = BaseSelector::getRecommendRender($this, $news_model->channel_id);

        $models = $recommend_selector->select($news_model->url_sign);
        $render = BaseListRender::getRender($this, $news_model->channel->id);

        $ret["recommend_news"]= $render->render($models);
        $this->logEvent(EVENT_NEWS_RECOMMEND, array(
                                                    "recommend"=> array(
                                                                   "news" => array_keys($models),
                                                                   "policy"=> "random",
                                                                   ),
                                                    "ad" => ""
                                               ));
        $this->logger->info(sprintf("[Recommend][policy:%s][cnl:%d][sent:%d]",
                                    $recommend_selector->getPolicyTag(), 
                                    $news_model->channel_id, count($ret["recommend_news"])));
        $this->setJsonResponse($ret);
        return $this->response;
    }


    private function incrViewCount($newsSign) {
        $video_model = Video::getByNewsSign($newsSign);
        if (!$video_model) {
            return 0;
        }

        if (!isset($video_model->content_sign) || $video_model->content_sign == null || count($video_model->content_sign) == 0) {
            $video_model->content_sign = "";
        } 
        
        $originView = $video_model->view;
        $video_model->view += 1;

        $ret = $video_model->save();
        if (!$ret) {
            $this->logger->warning(sprintf("save error: %s", join(",",$video_model->getMessages())));
            throw new HttpException(ERR_INTERNAL_DB, "internal error");
        }
        return $originView;
    }
}
