<?php
/**
 * 
 * @file    CommentController.php
 * @authors Zhao Yulong (elysium.zyl@gmail.com)
 * @date    2016-10-27 17:51:18
 * @version $Id$
 */

class CommentController extends BaseController {
    public function IndexAction(){
        if ($this->request->isPost()) {
            return $this->addComment();
        } else if ($this->request->isGet()) {
            return $this->getComments();
        } else {
            throw new HttpException(ERR_INVALID_METHOD,
                                    "add new comment error");
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

        if (version_compare($this->client_version, RICH_COMMENT_FEATURE, ">=")) {
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
        $param = $this->request->getJsonRawBody(true);

        if (!$this->userSign) {
            throw new HttpException(ERR_NOT_AUTH, "usersign not set");
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
        
        list($resp, $status) = $comment_service->AddComment($req)->wait();

        if ($status->code != 0) {
            throw new HttpException(ERR_INTERNAL_BG,
                                    "get comment error:" . json_encode($status->details, true));
        }
        
        $s = $resp->getResponse();
        if ($s->getCode() != iface\GeneralResponse\ErrorCode::NO_ERROR) {
            throw new HttpException(ERR_INTERNAL_BG,
                                    "add comment error: " . $s->getErrorMsg()
                                    );
        }
        $this->logEvent(EVENT_NEWS_COMMENT, array(
                                                  "news_id" => $newsSign,
                                                  "ref_id" => $ref_id,
                                                  "anonymous" => $anonymous,
                                                  ));
        
        if (version_compare($this->client_version, RICH_COMMENT_FEATURE, ">=")) {
            $this->setJsonResponse(array(
                                         "message" => "ok",
                                         "id" => $resp->getCommentId(),
                                         "time" => time(),
                                         ));
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


    public function LikeAction(){
        if (!$this->request->isPost()){
            throw new HttpException(ERR_INVALID_METHOD,
                                    "add new comment error");
        }

        if (!$this->deviceId) {
            throw new HttpException(ERR_DEVICE_NON_EXISTS, "device id not found");
        }
        
        $param = $this->request->getJsonRawBody(true);
        $comment_id = $this->get_or_fail($param, "comment_id", "int");
        if ($this->userSign) {
            // if set user sign, he must exists
            // but client could ignoring this field
            $user_model = User::getBySign($this->userSign);
            if (!$user_model) {
                throw new HttpException(ERR_USER_NON_EXISTS,
                                        "user non exists");
            }
        }

        $comment_service = $this->di->get('comment');

        $req = new iface\LikeCommentRequest();
        $req->setProduct($this->config->comment->product_key);        
        $req->setCommentId($comment_id);
        $req->setDeviceId($this->deviceId);
        $req->setUserId($this->userSign);

        list($resp, $status) = $comment_service->LikeComment($req)->wait();

        $currentLiked = 0;
        if ($status->code != 0) {
            $this->logger->warning("communicate to comment server error");
        } else {
            $s = $resp->getResponse();
            if ($s->getCode() != iface\GeneralResponse\ErrorCode::NO_ERROR) {
                $this->logger->warning("communicate to comment server error: " . $s->getErrorMsg());
            } else {
                $currentLiked = $resp->getCurrentLiked();
                
                $this->logEvent(EVENT_NEWS_COMMENT_LIKE, array(
                                                               "comment_id" => $comment_id,
                                                               ));
            }
        }

        $this->setJsonResponse(array(
                                    "message" => "ok",
                                    "liked" => $currentLiked,
                                    ));

        return $this->response;
    }
}