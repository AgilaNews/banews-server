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

    private function getComments(){
        $newsSign = $this->get_request_param("news_id", "string", true);
        $news_model = News::getBySign($newsSign);
        if (!$news_model) {
            throw new HttpException(ERR_NEWS_NON_EXISTS, "news not exists");
        }
        $pn = $this->get_request_param("pn", "int");
        $last_id = $this->get_request_param("last_id", "string");
        $comments = Comment::getAll($news_model->id, $last_id, $pn);
        $ret = array();
        
        foreach ($comments as $comment) {
            array_push($ret, $this->serializeComment($comment));
        }

        $this->setJsonResponse($ret);
        return $this->response;
    }

    private function postComments() {
        if (!$this->userSign) {
            throw new HttpException(ERR_NOT_AUTH, "userid not seted");
        }

        $req = $this->request->getJsonRawBody(true);
        if (null === $req) {
            throw new HttpException(ERR_BODY_ERR, "body format error");
        }
        $newsSign = $this->get_or_fail($req, "news_id", "string");
        $comment_detail = $this->get_or_fail($req, "comment_detail", "string");
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
        $count = Comment::getCount($news_model->id, $user_model->id);
        if ($count > MAX_COMMENT_COUNT) {
            throw new HttpException(ERR_COMMENT_TOO_MUCH, "user commented too much");
        }
        
        $comment->user_id = $user_model->id;
        $comment->news_id = $news_model->id;
        $comment->user_comment = $comment_detail;
        $comment->create_time = time();
        
        $ret = $comment->save();
        if ($ret === false) {
            $this->logger->warning("save comment error : " . $comment->getMessages());
            throw new HttpException(ERR_INTERNAL_DB,
                                    "save comment info error");
        }

        $cache = $this->di->get('cache');
        if ($cache) {
            $cache->delete(Comment::getCacheKeys($news_model->id));
            $this->logger->info("clear cache success");
        } else {
            $this->logger->info("clear cache error");
        }

        $this->setJsonResponse(array("message" => "ok", 
                                    "comment" => $this->serializeComment($comment))
                                    );
        return $this->response;
    }

    private function postCollect(){
        if (!$this->userSign) {
            throw new HttpException(ERR_NOT_AUTH, "userid not seted");
        }

        $req = $this->request->getJsonRawBody(true);
        if (null == $req) {
            throw new HttpException(ERR_BODY_ERR, "body format error");
        }

        $user_model = User::getBySign($this->userSign);
        if (!$user_model) {
            throw new HttpException(ERR_USER_NON_EXISTS,
                                    "user not exists");
        }
        $newsSign = $this->get_or_fail($req, "news_id", "string");
        $news_model = News::getBySign($newsSign);
        if (!$news_model) {
            throw new HttpException(ERR_NEWS_NON_EXISTS, "news not found");
        }
        
        if (Collect::getCollectId($user_model->id, $news_model->id)) {
            throw new HttpException(ERR_COLLECT_CONFLICT, "user has collected this");
        }

        $collect_model = new Collect();
        $collect_model->user_id = $user_model->id;
        $collect_model->news_id = $news_model->id;
        $collect_model->create_time = time();
        $ret = $collect_model->save();
        if (!$ret) {
            throw new HttpException(ERR_INTERNAL_DB,
                                    "save collect model error");
        }

        $this->setJsonResponse(array("collect_id" => $collect_model->id, "message" => "ok"));
        return $this->response;
    }
    
    private function getCollect(){
        if (!$this->userSign) {
            throw new HttpException(ERR_NOT_AUTH, "userid not seted");
        }
        
        $user_model = User::getBySign($this->userSign);
        if (!$user_model) {
            throw new HttpException(ERR_INTERNAL_DB, "user not found");
        }
        
        $last_id = $this->get_request_param("last_id", "string");
        $pn = $this->get_request_param("pn", "int");

        $collects = Collect::getAll($user_model->id, $last_id, $pn);
        $ret = array();
        
        foreach ($collects as $collect) {
            array_push($ret, $this->serializeCollect($collect));
        }

        $this->setJsonResponse($ret);
        return $this->response;
    }

    private function delCollect() {
        if (!$this->userSign) {
            throw new HttpException(ERR_NOT_AUTH, "userid not seted");
        }
        
        $user_model = User::getBySign($this->userSign);
        if (!$user_model) {
            throw new HttpException(ERR_INTERNAL_DB, "user not found");
        }
        
        $req = $this->request->getJsonRawBody(true);
        if (null === $req) {
            throw new HttpException(ERR_BODY_ERR, "body format error");
        }
        
        if (!is_array($req["ids"])) {
            throw new HttpException(ERR_BODY_ERR, "ids must be array");
        }
        
        foreach (array_chunk($req["ids"], 500) as $subid) {
            $phql = "DELETE FROM tb_collect WHERE id IN (" . implode(",", $subid) . ")";
            $this->di->get('db')->execute($phql);
        }

        $this->setJsonResponse(array("message"=>"ok"));
        return $this->response;
    }
    
    private function serializeComment($comment){
        $ret = array (
                      "id" => $comment->id,
                      "time" => $comment->create_time,
                      "comment" => $comment->user_comment,
                      "user_id" => $comment->user_id,
                      "user_name" => "anonymous",
                      "user_portrait_url" => "",
                      );
        
        $user_model = User::getById($comment->user_id);
        if ($user_model) {
            $ret["user_name"] = $user_model->name;
            $ret["user_portrait_url"] = $user_model->portrait_url;
        }
        return $ret;
    }

    private function serializeCollect($collect){
        $news_model = News::getById($collect->news_id, array("id", "url_sign", "title", "source_name", "source_url"));
        if (!$news_model) {
            continue;
        }
        $imgs = NewsImage::getImagesOfNews($news_model->url_sign);
        
        $ret = array (
                      "collect_id" => $collect->id,
                      "public_time" => $collect->create_time,
                      "news_id" => $news_model->url_sign,
                      "title" => $news_model->title,
                      "source" => $news_model->source_name,
                      "source_url" => $news_model->source_url,
                      );
        return array_merge($ret, ImageHelper::formatImageAndTpl($imgs));
    }
}
