<?php
/**
 * @file   NotificationController.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Tue Oct 18 22:55:30 2016
 * 
 * @brief  
 * 
 * 
 */
class NotificationController extends BaseController {
    public function IndexAction(){
        if (!$this->request->isGet()) {
            throw new HttpException(ERR_INVALID_METHOD, "not supported method");
        }

        return $this->getNotifications();
    }

    private function getNotifications(){
        if (!$this->userSign) {
            throw new HttpException(ERR_NOT_AUTH, "usersign not set");
        }
        $user_model = User::getBySign($this->userSign);
        if (!$user_model) {
            throw new HttpException(ERR_USER_NON_EXISTS,
                                    "user non exists");
        }
        $last_id = $this->get_request_param("last_id", "int", false, 0);
        $length = $this->get_request_param("pn", "int", false, 20);
        
        $req = new iface\GetNotificationRequest();

        $req->setProduct($this->config->comment->product_key);
        $req->setUserId($this->userSign);
        $req->setLastId($last_id);
        $req->setLength($length);

        $comment_service = $this->di->get('comment');
        list($resp, $status) = $comment_service->GetNotifications($req)->wait();
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

        $ret = array();

        foreach ($resp->getNotifications() as $notify) {
            $cell = Comment::renderComment($notify->getComment());
            $cell["status"] = $notify->getStatus();
            if ($cell["status"] == null) {
                $cell["status"] = 0;
            }
            $ret []= $cell;
        }
        

        $this->setJsonResponse($ret);
        return $this->response;
    }
}

