<?php
/**
 * @file   CommentController.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Mon Oct 10 22:56:33 2016
 * 
 * @brief  
 * 
 * 
 */
define('DEFAULT_HOT_COUNT', 3);
define('DEFAULT_HOT_LIKED_COUNT', 3);
define('DEFAULT_NEW_COUNT', 5);

class CommentController extends BaseController {
    public function IndexAction(){
        if ($this->request->isPost()) {
            return $this->addComment();
        } else if ($this->request->isGet()) {
            return $this->getComment();
        } else {
            throw new HttpException(ERR_INVALID_METHOD,
                                    "add new comment error");
        }
    }

    private function getComment(){
        $newsSign = $this->get_request_param("news_id", "string", true);
        $news_model = News::getBySign($newsSign);
        if (!$news_model) {
            throw new HttpException(ERR_NEWS_NON_EXISTS,
                                    "news not exists");
        }

        $ret = array(
                     "new" => $this->getCommentByFilter($newsSign, "new"),
                     "hot" =>$this->getCommentByFilter($newsSign, "hot"),
                     );

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
                                    "get comment error:" . $status->details);
        }
        
        $s = $resp->getResponse();
        if ($s->getCode() != iface\GeneralResponse\ErrorCode::NO_ERROR) {
            throw new HttpException(ERR_INTERNAL_BG,
                                    "add comment error: " . $s->getErrorMsg()
                                    );
        }

        $this->setJsonResponse(array(
                                 "message" => "ok",
                                 "comment_id" => $resp->getCommentId(),
                                 ));
            
        
        return $this->response;
    }

    public function getCommentByFilter($newsSign, $filter) {
        $comment_service = $this->di->get('comment');

        $last_id = $this->get_request_param("last_id", "int", false, 0);
        $length = $this->get_request_param("pn", "int", false, 20);
        
        $req = new iface\GetCommentsOfDocRequest();
        $req->setProduct($this->config->comment->product_key);
        $req->setDocId($newsSign);
        $req->setLastId($last_id);
        $req->setDeviceId($this->deviceId);
        
        if ($filter == "new") {
            if ($last_id == 0) {
                $req->setLength(DEFAULT_NEW_COUNT);
            } else {
                $req->setLength($length);
            }
            $req->setOrder(iface\GetCommentsOfDocRequest\OrderField::TIME);
        } else if ($filter == "hot") {
            if ($last_id == 0) {
                $req->setLength(DEFAULT_HOT_COUNT);
                $req->setThreshold(DEFAULT_HOT_LIKED_COUNT);
                $req->setOrder(iface\GetCommentsOfDocRequest\OrderField::LIKED);
            }
        } else {
            assert(false, "filter is invalid : " . $filter);
        }
        
        list($resp, $status) = $comment_service->GetCommentsByDoc($req)->wait();
        if ($status->code != 0) {
            throw new HttpException(ERR_INTERNAL_BG,
                                    "get comment error:" . $status->details);
        }
        
        $s = $resp->getResponse();
        if ($s->getCode() != iface\GeneralResponse\ErrorCode::NO_ERROR) {
            throw new HttpException(ERR_INTERNAL_BG,
                                    "add comment error: " . $s->getErrorMsg()
                                    );
        }

        $comments = $resp->getCommentsList();
        $ret = array();
        
        foreach ($comments as $comment) {
            $cell = array("comment" => $comment->getCommentDetail(),
                          "id" => $comment->getCommentId(),
                          "user_id" => $comment->getUserId(),
                          "user_name" => "anonymous",
                          "user_portrait_url" => "",
                          "device_liked" => $comment->getDeviceLiked() || false,
                          "liked" => $comment->getLiked(),
                          "reply" => new stdClass(),
                          );

            if ($cell["liked"] == null) {
                $cell["liked"] = 0; // this is maybe a php protobuf bug towards proto syntax3
            }

            $user_model = User::getBySign($comment->getUserId());
            if ($user_model) {
                $cell["user_name"] = $user_model->name;
                $cell["user_portrait_url"] = $user_model->portrait_url;
            }

            $ref_comments = $comment->getRefComments();
            if (count($ref_comments) > 0) {
                $ref_comment = $ref_comments[0];
                $cell["reply"] = array(
                                      "user_id" => $ref_comment->getUserId(),
                                      "user_name" => "anonymous",
                                      "user_portrait_url" => "",
                                      "comment" => $ref_comment->getCommentDetail(),
                                      );
                $ref_user = User::getBySign($ref_comment->getUserId());
                if ($ref_user) {
                    $cell["user_name"] = $ref_user->name;
                    $cell["user_portrait_url"] = $ref_user->portrait_url;
                }
            }

            $ret []= $cell;
        }

        return $ret;
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

        if ($status->code != 0) {
            throw new HttpException(ERR_INTERNAL_BG,
                                    $status->details);
        }
        
        $s = $resp->getResponse();
        if ($s->getCode() != iface\GeneralResponse\ErrorCode::NO_ERROR) {
            throw new HttpException(ERR_INTERNAL_BG,
                                    $s->getErrorMsg()
                                    );
        }

        $currnetLiked = $resp->getCurrentLiked();
        $this->setJsonResponse(array(
                                    "message" => "ok",
                                    "liked" => $currnetLiked,
                                    ));

        return $this->response;
    }
}
