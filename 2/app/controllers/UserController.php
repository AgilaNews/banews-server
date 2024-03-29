<?php
/**
 * @file   User.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Sun Apr 24 15:01:48 2016
 * 
 * @brief  
 * 
 * 
 */
class UserController extends BaseController {
    const AnimationNews = array(BANNER_NEWS_ID);
    public function UnlikeAction() {
        if (!$this->request->isPost()) {
            throw new HttpException(ERR_INVALID_METHOD, "not supported method");
        }

        $param = $this->request->getJsonRawBody(true);

        $news_id = $this->get_or_fail($param, "news_id", "string");
        $reasons = $param["reasons"];

        $this->logger->info(sprintf("[UserUnlike][news:%d]", $news_id));

        foreach ($reasons as $reason) {
            $reason_type = $this->get_or_fail($reason, "id", "string");
            $reason_name = $this->get_or_fail($reason, "name", "string");
            
            $model = new UserUnlike();
            if ($this->userSign) {
                $model->user_id = $this->userSign;
            }
            $model->device_id = $this->deviceId;
            $model->news_id = $news_id;
            $model->reason_type = $reason_type;
            $model->reason_name = $reason_name;
            $model->upload_time = time();
            $model->save();
            $this->logger->info(sprintf("[name:%d]", $reason_name));
        }
        $this->setJsonResponse(
            array("message" => "OK")
            );
        return $this->response;
    }

    public function CommentAction(){
        if ($this->request->isPost()) {
            return $this->addComment();
        } else if ($this->request->isGet()) {
            return $this->getComments();
        } else {
            throw new HttpException(ERR_INVALID_METHOD, "not supported method");
        }
    }

    public function CollectAction(){
        if ($this->request->isPost()) {
            return $this->postCollect();
        } else if ($this->request->isGet()){
            return $this->getCollect();
        } else if ($this->request->isDelete()) {
            return $this->delCollect();
        } else {
            throw new HttpException(ERR_INVALID_METHOD, "not supported method");
        }
    }

    private function getComments(){
        $newsSign = $this->get_request_param("news_id", "string", true);
        $news_model = News::getBySign($newsSign);
        if (!$news_model) {
            throw new HttpException(ERR_NEWS_NON_EXISTS,
                                    "news not exists");
        }
        $last_id = $this->get_request_param("last_id", "int", false, 0);
        $hot_length = $this->get_request_param("hot_pn", "int", false, 10);
        $length = $this->get_request_param("pn", "int", false, 20);

        if (Features::Enabled(Features::RICH_COMMENT_FEATURE, $this->client_version, $this->os)) {
            if ($length > 0) {
                $ret["new"] = Comment::getCommentByFilter($this->deviceId, $newsSign, $last_id, $length, "new");
            } else {
                $ret["new"] = 0;
            }
    
            if ($hot_length > 0) {
                $ret["hot"] = Comment::getCommentByFilter($this->deviceId, $newsSign, $last_id, $hot_length, "hot");
            } else {
                $ret["hot"] = array();
            }
            
            $this->logger->info(sprintf("[GetComment][news:%s][last:%d][limit:%d][hot:%d][new:%d]", $newsSign,
                                        $last_id, $length, count($ret["hot"]), count($ret["new"])));
        } else {
            $ret = Comment::getCommentByFilter($this->deviceId, $newsSign, $last_id, $length, "new");
            $this->logger->info(sprintf("[GetComment][news:%s][last:%d][limit:%d][new:%d]", $newsSign,
                                        $last_id, $length, count($ret)));
        }

        $this->setJsonResponse($ret);
        return $this->response;
    }

    private function addComment(){
        $comment_service = $this->di->get('comment');
        $config = $this->di->get('config');
        $param = $this->request->getJsonRawBody(true);

        if (!$this->userSign) {
            throw new HttpException(ERR_NOT_AUTH, "usersign not set");
        }

        if (!$comment_service) {
            throw new HttpException(ERR_INTERNAL_DB, "internal error");
        }
        
        $newsSign = $this->get_or_fail($param, "news_id", "string");
        $detail = $this->get_or_fail($param, "comment_detail", "string");
        
        $ref_id = $this->get_or_default($param, "ref_id", "int", 0);
        $anonymous = $this->get_or_default($param, "anonymous", "bool", false);
           
        $req = new iface\NewCommentRequest();
        $req->setProduct($this->config->comment->product_key);

        $user_model = User::getBySign($this->userSign);
        if (!$user_model) {
            throw new HttpException(ERR_USER_NON_EXISTS,
                                    "user non exists");
        }
        $news_model = News::getBySign($newsSign);
        if (!$news_model) {
            throw new HttpException(ERR_NEWS_NON_EXISTS,
                                    "news not exists");
        }
        
        $req->setUserId($this->userSign);
        $req->setDocId($newsSign);
        $req->setCommentDetail($detail);
        $req->setDeviceId($this->deviceId);
        $req->setRefCommentId($ref_id);
        $req->setIsAnonymous($anonymous);
        
        list($resp, $status) = $comment_service->AddComment($req,
                                                            array(),
                                                            array(
                                                                  "timeout" => $config->comment->call_timeout
                                                                  )
                                                            )->wait();

        if ($status->code != 0) {
            throw new HttpException(ERR_INTERNAL_BG,
                                    "get comment error:" . json_encode($status->details, true));
        }
        
        $this->logEvent(EVENT_NEWS_COMMENT, array(
                                                  "news_id" => $newsSign,
                                                  "ref_id" => $ref_id,
                                                  "anonymous" => $anonymous,
                                                  ));
        
        if (Features::Enabled(Features::RICH_COMMENT_FEATURE, $this->client_version, $this->os)) {
            $ret = array(
                "message" => "ok",
                "id" => $resp->getCommentId(),
                "time" => time(),
                );
            //merry christmas comment
            $cache = $this->di->get("cache");
            $keywords = $cache->lrange("MERRY_CHRISTMAS_ANIMOTION_WORDS", 0, -1);
            foreach ($keywords as $keyword) {
                if (strpos(strtolower($detail), $keyword) !== false) {
                    $ret["Animation"] = 1;
                    break;
                }
            }
            $this->setJsonResponse($ret);
        } else {
            $this->setJsonResponse(array(
                                         "message" => "ok",
                                         "comment" => array(
                                                            "id" => $resp->getCommentId(),
                                                            "time" => time(),
                                                            "comment" => $detail,
                                                            "user_id" => $user_model->sign,
                                                            "user_name" => $user_model->name,
                                                            "user_portrait_url" => $user_model->portrait_url,
                                                            ),
                                         ));
        }
            
        
        return $this->response;
    }


    private function batchCollect(){
        if (!$this->userSign) {
            throw new HttpException(ERR_NOT_AUTH, "userid not seted");
        }

        $req = $this->request->getJsonRawBody(true);
        if (null == $req) {
            throw new HttpException(ERR_BODY, "body format error");
        }

        
    }
    private function postCollect(){
        if (!$this->userSign) {
            throw new HttpException(ERR_NOT_AUTH, "userid not seted");
        }

        $req = $this->request->getJsonRawBody(true);
        if (null == $req) {
            throw new HttpException(ERR_BODY, "body format error");
        }

        $user_model = User::getBySign($this->userSign);
        if (!$user_model) {
            throw new HttpException(ERR_USER_NON_EXISTS,
                                    "user not exists");
        }

        $news_ids = array();
        $ret = array();
        
        foreach ($req as $cell) {
            $news_model = News::getBySign($cell["news_id"]);
            if (!$news_model) {
                throw new HttpException(ERR_NEWS_NON_EXISTS, "news not found");
            }
            $news_ids []= $cell["news_id"];
        
            if(($saved_cid = Collect::getCollectId($this->userSign, $news_model->url_sign))) {
                //                throw new HttpException(ERR_COLLECT_CONFLICT, "user has collected this", array("collect_id" => $saved_cid));
            } else {
                $collect_model = new Collect();
                $collect_model->user_sign = $this->userSign;
                $collect_model->news_sign = $news_model->url_sign;
                $collect_model->create_time = $cell["ctime"];
                if (!$collect_model->create_time) {
                    $collect_model->create_time = time();
                }
                $result = $collect_model->save();
                if (!$result) {
                    throw new HttpException(ERR_INTERNAL_DB,
                                            "save collect model error: " .  $collect_model->getMessages()[0]);
                }
                $saved_cid = $collect_model->id;
            }

            $ret []= array(
                           "news_id" => $cell["news_id"],
                           "collect_id" => $saved_cid,
                           );
        }
            
        $this->logEvent(EVENT_NEWS_COLLECT, array("news_id" => $news_ids));
        $this->logger->info(sprintf("[PostCollect][news:%s]",
                                     json_encode($news_ids)));
        $this->setJsonResponse($ret);
        return $this->response;
    }
    
    private function getCollect(){
        if (!$this->userSign) {
            throw new HttpException(ERR_NOT_AUTH, "userid not seted");
        }
        
        $user_model = User::getBySign($this->userSign);
        if (!$user_model) {
            throw new HttpException(ERR_USER_NON_EXISTS, "user not found");
        }
        
        $last_id = $this->get_request_param("last_id", "string");
        $pn = $this->get_request_param("pn", "int");

        $collects = Collect::getAll($this->userSign, $last_id, $pn);
        $ret = array();
        $signs = array();

        $render = new CollectListRender($this);
        $ret = $render->render($collects);

        $this->logger->info(sprintf("[GetCollect][last:%d][limit:%d][ret:%d]",
                                     $last_id, $pn, count($ret)));
        $this->setJsonResponse($ret);
        return $this->response;
    }

    private function delCollect() {
        if (!$this->userSign) {
            throw new HttpException(ERR_NOT_AUTH, "userid not seted");
        }
        
        $user_model = User::getBySign($this->userSign);
        if (!$user_model) {
            throw new HttpException(ERR_USER_NON_EXISTS, "user not found");
        }
        
        $req = $this->request->getJsonRawBody(true);
        if (null === $req) {
            throw new HttpException(ERR_BODY, "body format error");
        }
        
        if (!is_array($req["ids"])) {
            throw new HttpException(ERR_BODY, "ids must be array");
        }
        
        Collect::batchDelete($req["ids"]);
        $this->logger->info(sprintf("[DelCollect][news:%s]", json_encode($req["ids"])));
        
        $this->setJsonResponse(array("message"=>"ok"));
        return $this->response;
    }
    
    private function serializeCollect($collect){
        $imgs = NewsImage::getImagesOfNews($news_model->url_sign);
        
        $ret = array (
                      "collect_id" => $collect->id,
                      "public_time" => $collect->create_time,
                      "news_id" => $news_model->url_sign,
                      "title" => $news_model->title,
                      "source" => $news_model->source_name,
                      "source_url" => $news_model->source_url,
                      );
        return array_merge($ret, ImageHelper::formatImageAndTpl($imgs, $this->deviceModel, true));
    }
}
