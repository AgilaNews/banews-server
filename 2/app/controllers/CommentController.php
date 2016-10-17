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
