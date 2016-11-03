<?php
/**
 * 
 * @file    NewsController.php
 * @authors Zhao Yulong (elysium.zyl@gmail.com)
 * @date    2016-10-27 17:15:30
 * @version $Id$
 */

define ("VIDEO_CHANNEL_ID", 30001);
use Phalcon\Mvc\Model\Query;

class NewsController extends BaseController {
    
/*    public function DetailAction() {
        if (!$this->request->isGet()){
            throw new HttpException(ERR_INVALID_METHOD,
                "read news must be get");
        }

        $channel_id = $this->get_request_param("channel_id", "int", true);

        $newsSign = $this->get_request_param("news_id", "string", true);
        $news_model = News::getBySign($newsSign);
        if (!$news_model) {
            throw new HttpException(ERR_NEWS_NON_EXISTS, "news not found");
        }
    }*/

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

        $cname = "Selector$channel_id";
        if (class_exists($cname)) {
            $selector = new $cname($channel_id, $this); 
        } else {
            $selector = new BaseNewsSelector($channel_id, $this);
        }

        $models = $selector->select($prefer);
        
        foreach ($models as $sign => $model) {
            $dispatch_ids []= $sign;
        }

        $cname = "Render$channel_id";
        if (class_exists($cname)) {
            $render = new $cname($this);
        } else {
            $render = new BaseListRender($this);
        }

        $dispatch_id = substr(md5($prefer . $channel_id . $this->deviceId . time()), 16);
        $ret[$dispatch_id] = $render->render($models);

        $this->logger->info(sprintf("[List][dispatch_id:%s][policy:%s][pfer:%s][cnl:%d][sent:%d]",
                                    $dispatch_id, $selector->getPolicyTag(), $prefer, 
                                    $channel_id, count($ret[$dispatch_id])));

        $this->logEvent(EVENT_NEWS_LIST, array(
                                              "dispatch_id"=> $dispatch_id,
                                              "news"=> $dispatch_ids,
                                              "policy"=> $selector->getPolicyTag(),
                                              "channel_id" => $channel_id,
                                              "prefer" => $prefer,
                                              ));
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

        $recommend_selector = new BaseRecommendNewsSelector($news_model->channel_id, $this);
        $models = $recommend_selector->select($news_model->url_sign);
        $cname = "RecommendRender" . $news_model->channel_id;
        if (class_exists($cname)) {
            $render = new $cname($this);
        } else {
            $render = new BaseListRender($this);
        }

        $ret["recommend_news"]= $render->render($models);
        if ($this->userSign) {
            $ret["collect_id"] = Collect::getCollectId($this->userSign, $newsSign);
        }
        $this->setJsonResponse($ret);
        return $this->response;
    }

    public function ViewAction() {
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

        
        $originView = $now->liked;
        $pseudoView = mt_rand(1, 10);
        if ($pseudoView == 1) {
            $now->viewed += 2;
            $this->logger->info(sprintf("[pseudo:%d]", $now->viewed));
        } else {
            $now->viewed += 1;
        }

        $ret = $now->save();
        if (!$ret) {
            $this->logger->warning(sprintf("save error: %s", join(",",$now->getMessages())));
            throw new HttpException(ERR_INTERNAL_DB, "internal error");
        }

        $ret = array (
            "message" => "ok",
            "viewed" => $originLike + 1,
        );

        $this->logger->info(sprintf("[View][viewed:%s]", $ret["viewed"]));
        $this->logEvent(EVENT_NEWS_VIEW, array("news_id"=>$newsSign, "viewed"=>$ret["viewed"]));
        $this->setJsonResponse($ret);
        return $this->response;

    }
}
