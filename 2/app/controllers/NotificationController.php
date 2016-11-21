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

define('REPLY_COMMENT_NOTIFICATION_TYPE', 1);
define('LIKE_NOTIFICATION_TYPE', 3);
define('LIKE_NOTIFY_FEATURE', "1.2.5");
class NotificationController extends BaseController {

    public function IsLikeNotifyVersion(){
        if ($this->os == "android" and version_compare($this->client_version, LIKE_NOTIFY_FEATURE, "<")) {
            return False;
        }  
        return True;
    }
    public function IndexAction(){
        if (!$this->request->isGet()) {
            throw new HttpException(ERR_INVALID_METHOD, "not supported method");
        }
        $this->check_user_and_device();
        
        $last_id = $this->get_request_param("last_id", "int", false, 0);
        $length = $this->get_request_param("pn", "int", false, 20);
        
        $req = new iface\GetNotificationRequest();

        $req->setProductId($this->config->comment->product_key);
        $req->setUserId($this->userSign);
        $req->setLastId($last_id);
        $req->setLength($length);

        $comment_service = $this->di->get('comment');
        list($resp, $status) = $comment_service->GetNotifications($req)->wait();
                if ($status->code != 0) {
            throw new HttpException(ERR_INTERNAL_BG,
                                    "get comment error:" . json_encode($status->details, true));
        }
        
        $ret = array();

        foreach ($resp->getNotifications() as $notify) {
            $notiType = $notify->getType();
            if ($notiType == REPLY_COMMENT_NOTIFICATION_TYPE){ 
                $replyMsg = $notify->getReplyMsg();
                $cell = Comment::renderComment($replyMsg->getComment());
            }
            else if ($notiType == LIKE_NOTIFICATION_TYPE){
                if (!$this->IsLikeNotifyVersion()){
                    continue;
                }
                $LikeMsg = $notify->getLikeMsg();
                $cell = Comment::renderLikeComment($LikeMsg->getComment(), $LikeMsg->getLikeNumber());
                //!!ugly code to judge tpl, reconstrut it later
                $sign = $cell["news_id"];
                $news_model = News::getBySign($sign);
                $channel_id = $news_model->channel_id;
                $cname = "Render$channel_id";
                if (class_exists($cname)) {
                    $render = new $cname($this);
                } else {
                    $render = new BaseListRender($this);
                }
                $news_cell = $render->render(array($sign => $news_model))[0];
                $cell["tpl"] = $news_cell["tpl"];
            }
            else{
                continue;
            }
            $cell["notify_id"] = $notify->getNotificationId();
            $cell["status"] = $notify->getStatus();
            $cell["type"] = $notiType;
            if ($cell["status"] == null) {
                $cell["status"] = 0;
            }
            $ret []= $cell;
        }
        

        $this->setJsonResponse($ret);
        return $this->response;
    }

    public function RelatedAction(){
        if (!$this->request->isGet()) {
            throw new HttpException(ERR_INVALID_METHOD, "not supported method");
        }
        $this->check_user_and_device();

        $notify_id = $this->get_request_param("id", "int", true);
        $last_id = $this->get_request_param("last_id", "int", false, 0);
        $pn = $this->get_request_param("pn", "int", false, 20);

        if ($last_id != 0) {
            $this->setJsonResponse(array());
            return $this->response;
        }
        
        $comment_service = $this->di->get('comment');
        $req = new iface\GetRelatedCommentOfNotificationRequest();
        
        $req->setProductId($this->config->comment->product_key);
        $req->setDeviceId($this->deviceId);
        $req->setLastId($last_id);
        $req->setUserId($this->userSign);
        $req->setLength($pn);
        $req->setNotificationId($notify_id);

        list($resp, $status) = $comment_service->GetRelatedCommentOfNotification($req)->wait();
                if ($status->code != 0) {
            throw new HttpException(ERR_INTERNAL_BG,
                                    json_encode($status->details, true));
        }
        
        $sign = $resp->getDocId();
        $news_model = News::getBySign($sign);

        $channel_id = $news_model->channel_id;
        $cname = "Render$channel_id";
        if (class_exists($cname)) {
            $render = new $cname($this);
        } else {
            $render = new BaseListRender($this);
        }

        $news_cell = $render->render(array($sign => $news_model))[0];
        $ret = array(
                     "related_news" => $news_cell,
                     "comments" => array(),
                     );

        foreach ($resp->getComments() as $comment) {
            $ret["comments"] []= Comment::renderComment($comment);
        }

        $this->setJsonResponse($ret);
        return $this->response;
    }
    public function checkAction(){
        if (!$this->request->isGet()) {
            throw new HttpException(ERR_INVALID_METHOD, "not supported method");
        }
        $this->check_user_and_device();
        $latest_id = $this->get_request_param("latest_id", "int", false, 0);
        
        $comment_service = $this->di->get('comment');
        $req = new iface\CheckNotificationRequest();
        
        $req->setProductId($this->config->comment->product_key);
        $req->setDeviceId($this->deviceId);
        $req->setUserId($this->userSign);
        $req->setLatestId($latest_id);

        list($resp, $status) = $comment_service->CheckNotification($req)->wait();
        if ($status->code != 0) {
            throw new HttpException(ERR_INTERNAL_BG,
                                    json_encode($status->details, true));
        }
        $result = $resp->getHasNew();
        if($result == null){
            $result = 0;
        }
        $ret = array(
            "status"=>strval($result)
        );
        $this->setJsonResponse($ret);
        return $this->response;
    }
    public function readAction(){
        if (!$this->request->isGet()) {
            throw new HttpException(ERR_INVALID_METHOD, "not supported method");
        }
        $this->check_user_and_device();
        $notification_id = $this->get_request_param("notification_id", "int", false, 0);
        $comment_service = $this->di->get('comment');
        $req = new iface\ReadNotificationRequest();
        $req->setProductId($this->config->comment->product_key);
        $req->setDeviceId($this->deviceId);
        $req->setUserId($this->userSign);
        $req->setNotificationId($notification_id);
        list($resp, $status) = $comment_service->CheckNotification($req)->wait();
        if ($status->code != 0) {
            throw new HttpException(ERR_INTERNAL_BG,
                                    json_encode($status->details, true));
        }
        $result = $resp->getStatus();
        if($result == 0){
            $this->setJsonResponse(array("message" => "ok"));
        }
        else{
            $this->setJsonResponse(array("message" => "fail"));
        }
        return $this->response;
    }
}

