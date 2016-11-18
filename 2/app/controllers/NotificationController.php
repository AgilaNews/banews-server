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
            $cell = Comment::renderComment($notify->getComment());
            $cell["notify_id"] = $notify->getNotificationId();
            $cell["status"] = $notify->getStatus();
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
        $product_id = "agilanews";
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
}

