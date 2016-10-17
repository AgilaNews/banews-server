<?php
use Phalcon\DI;

define('DEFAULT_HOT_LIKED_COUNT', 3);

class Comment{
    public static function getCommentByFilter($deviceId, $newsSign, $last_id, $length, $filter) {
        $di = DI::getDefault();
        $config = $di->get("config");
        $comment_service = $di->get('comment');

        $req = new iface\GetCommentsOfDocRequest();
        $req->setProduct($config->comment->product_key);
        $req->setDocId($newsSign);
        $req->setLastId($last_id);
        $req->setDeviceId($deviceId);
        $req->setLength($length);
        
        if ($filter == "new") {
            $req->setOrder(iface\GetCommentsOfDocRequest\OrderField::TIME);
        } else if ($filter == "hot") {
            if ($last_id == 0) {
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
                                      "liked" => $ref_comment->getLiked(),
                                      "device_liked" => $ref_comment->getDeviceLiked() || false,
                                      "comment" => $ref_comment->getCommentDetail(),
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

            $ret []= $cell;
        }

        return $ret;
    }

    public static function getCount($newsSign) {

    }
}
