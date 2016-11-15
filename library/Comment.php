<?php
use Phalcon\DI;

define('DEFAULT_HOT_LIKED_COUNT', 3);

class Comment{
    public static function getCommentByFilter($deviceId, $newsSign, $last_id, $length, $filter) {
        $di = DI::getDefault();
        $config = $di->get("config");
        $comment_service = $di->get('comment');
        if ($comment_service) {
            return array();
        }
        
        $logger = $di->get('logger');

        $req = new iface\GetCommentsOfDocRequest();
        $req->setProduct($config->comment->product_key);
        $req->setDocId($newsSign);
        $req->setLastId($last_id);
        $req->setDeviceId($deviceId);
        $req->setLength($length);
        
        if ($filter == "new") {
            $req->setOrder(iface\GetCommentsOfDocRequest\OrderField::TIME);
        } else if ($filter == "hot") {
            $req->setThreshold(DEFAULT_HOT_LIKED_COUNT);
            $req->setOrder(iface\GetCommentsOfDocRequest\OrderField::LIKED);
        } else {
            assert(false, "filter is invalid : " . $filter);
        }
        
        list($resp, $status) = $comment_service->GetCommentsByDoc($req)->wait();
        if ($status->code != 0) {
            $logger->warning("get comment error:" . json_encode($status->details, true));
            return array();
        }
        
        $comments = $resp->getCommentsList();
        $ret = array();

        foreach ($comments as $comment) {
            $ret []= self::renderComment($comment);
        }
        return $ret;
    }

    public static function getCount($newsSignList) {
        $di = DI::getDefault();
        $config = $di->get("config");
        $comment_service = $di->get('comment');
        
        $logger = $di->get("logger");
        $ret = array();
        foreach ($newsSignList as $sign) {
            $ret[$sign] = 0;
        }

        if (!$comment_service) {
            return $ret;
        }

        $req = new iface\GetCommentsCountRequest();
        $req->setProduct($config->comment->product_key);
        $req->setDocIds($newsSignList);
        
        list($resp, $status) = $comment_service->GetCommentsCount($req)->wait();
        if ($status->code != 0) {
            $logger->warning("get comment error:" . $status->code . ":" . json_encode($status->details, true));
            return $ret;
        }

        
        $ret = array();
        
        $count = $resp->getCommentsCountList();
        if (count($count) != count($newsSignList)) {
            $logger->warn("mismatched comment count");
            return $ret;
        }
        
        foreach ($newsSignList as $idx => $sign) {
            $ret[$sign] = $count[$idx];
        }

        return $ret;
    }

    public static function renderComment($comment){
        $cell = array("comment" => $comment->getCommentDetail(),
                      "id" => $comment->getCommentId(),
                      "user_id" => $comment->getUserId(),
                      "user_name" => "anonymous",
                      "user_portrait_url" => "",
                      "device_liked" => $comment->getDeviceLiked() || false,
                      "liked" => $comment->getLiked(),
                      "time" => $comment->getTimeStamp(),
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
        
        $ref_comment = $comment->getRefComment();
        if ($ref_comment) {
            $cell["reply"] = array(
                                   "id" => $ref_comment->getCommentId(),
                                   "user_id" => $ref_comment->getUserId(),
                                   "user_name" => "anonymous",
                                   "user_portrait_url" => "",
                                   "liked" => $ref_comment->getLiked(),
                                   "device_liked" => $ref_comment->getDeviceLiked() || false,
                                   "comment" => $ref_comment->getCommentDetail(),
                                   "time" => $ref_comment->getTimeStamp(),
                                   );
            if ($cell["reply"]["liked"] == null) {
                $cell["reply"]["liked"] = 0;
                }
            
            $ref_user = User::getBySign($ref_comment->getUserId());
            
            if ($ref_user) {
                $cell["reply"]["user_name"] = $ref_user->name;
                $cell["reply"]["user_portrait_url"] = $ref_user->portrait_url;
            }
        }
        
        return $cell;
    }
}
