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
    public function CommentAction(){
        if ($this->request->isPost()) {
            return $this->postComments();
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

    /*
     1=>2=>3=>4=>5
     ---> prefer: later
     <--- prefer: older
    */
    private function getComments(){
        $newsSign = $this->get_request_param("news_id", "string", true);
        $news_model = News::getBySign($newsSign);
        if (!$news_model) {
            throw new HttpException(ERR_NEWS_NON_EXISTS, "news not exists");
        }
        
        $pn = $this->get_request_param("pn", "int", false, 1000);
        $last_id = $this->get_request_param("last_id", "string");
        $prefer = $this->get_request_param("prefer", "string", false, "older");
        
        $comments = Comment::getAll($newsSign, $last_id, $pn, $prefer);
        $ret = array();
        
        foreach ($comments as $comment) {
            array_push($ret, $this->serializeComment($comment));
        }
        if ($prefer == "older") {
            $ret = array_reverse($ret);
        }

        $this->logger->info(sprintf("[GetComment][news:%s][last:%d][limit:%d][cmtcnt:%d]", $newsSign,
                                     $last_id, $pn, count($comments)));
        $this->setJsonResponse($ret);
        return $this->response;
    }

    private function postComments() {
        if (!$this->userSign) {
            throw new HttpException(ERR_NOT_AUTH, "userid not seted");
        }

        $req = json_decode($this->request->getRawBody(), true);
        if (!$req) {
            throw new HttpException(ERR_BODY, "body format error");
        }
        $newsSign = $this->get_or_fail($req, "news_id", "string");
        $comment_detail = $this->get_or_fail($req, "comment_detail", null);
        if (strlen($comment_detail) > MAX_COMMENT_SIZE) {
            throw new HttpException(ERR_COMMENT_TOO_LONG, "comment too long");   
        }

        $news_model = News::getBySign($newsSign);
        $comment = new Comment();
        $user_model = User::getBySign($this->userSign);
        if (!$user_model) {
            throw new HttpException(ERR_USER_NON_EXISTS,
                                    "user not exists");
        }
        if (!$news_model) {
            throw new HttpException(ERR_NEWS_NON_EXISTS,
                                    "news not exists");
        }
        $count = Comment::getCount($newsSign, $this->userSign);
        if ($count > MAX_COMMENT_COUNT) {
            throw new HttpException(ERR_COMMENT_TOO_MUCH, "user commented too much");
        }
        
        $comment->user_sign = $this->userSign;
        $comment->news_sign = $news_model->url_sign;
        $comment->user_comment = $comment_detail;
        $comment->create_time = time();
        
        $ret = $comment->save();
        if (!$ret) {
            $this->logger->warning("save comment error : " . $comment->getMessages()[0]);
            throw new HttpException(ERR_INTERNAL_DB,
                                    "save comment info error");
        }

        $this->logger->info(sprintf("[PostComment][news:%s][ci:%s]",
                                     $newsSign, $comment->id));
        $this->setJsonResponse(array("message" => "ok", 
                                    "comment" => $this->serializeComment($comment))
                                    );
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
                $result = $collect_model->save();
                if (!$result) {
                    throw new HttpException(ERR_INTERNAL_DB,
                                            "save collect model error");
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
    
    private function serializeComment($comment){
        $ret = array (
                      "id" => $comment->id,
                      "time" => $comment->create_time,
                      "comment" => $comment->user_comment,
                      "user_id" => $comment->user_sign,
                      "user_name" => "anonymous",
                      "user_portrait_url" => "",
                      );
        
        $user_model = User::getBySign($comment->user_sign);
        if ($user_model) {
            $ret["user_name"] = $user_model->name;
            $ret["user_portrait_url"] = $user_model->portrait_url;
        }
        return $ret;
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
