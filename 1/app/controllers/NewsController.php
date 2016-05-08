<?php
/**
 * @file   NewsController.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Thu May  5 10:45:33 2016
 * 
 * @brief  
 * 
 * 
 */

define('MAX_NEWS_SENT_COUNT', 200);

use Phalcon\Mvc\Model\Query;

class NewsController extends BaseController {
    public function DetailAction() {
        if (!$this->request->isGet()){
            throw new HttpException(ERR_INVALID_METHOD,
                "read news must be get");
        }

        $newsSign = $this->get_request_param("news_id", "string", true);
        $news_model = News::getBySign($newsSign);
        if (!$news_model) {
            throw new HttpException(ERR_NEWS_NON_EXISTS, "news not found");
        }

        $commentCount = Comment::getCount($news_model->id);
        $topComment = Comment::getAll($news_model->id, null, 3);

        $imgs = NewsImage::getImagesOfNews($newsSign);

        $ret = array(
            "body" => $news_model->json_text,
            "commentCount" => $commentCount,
            "comments" => array(), 
            "imgs" => ImageHelper::formatImgs($imgs),
            "recommend_news" => array(),
            "news_id" => $news_model->url_sign,
            "title" => $news_model->title,
            "source" => $news_model->source_name,
            "source_url" => $news_model->source_url,
            "public_time" => $news_model->publish_time,
            "share_url" => $news_model->shared_url,
            //"template" => $news_model->display_type,
            //"content_type" => $news_model->content_type,
            "channel_id" => $news_model->channel_id,
            "likedCount" => $news_model->liked,
            "collect_id" => 0,
        );


        if ($this->userSign) {
            $user_model = User::getBySign($this->userSign);
            if ($user_model) {
                $ret["collect_id"] = Collect::getCollectId($user_model->id, $news_model->id);
            }
        }

        foreach ($topComment as $comment) {
            array_push($ret["comments"], $this->serializeComment($comment));
        }

        $this->setJsonResponse($ret);
        return $this->response;
    }

    public function listAction(){
        if (!$this->request->isGet()) {
            throw new HttpException(ERR_INVALID_METHOD, "not supported method");
        }
        if (!$this->deviceId) {
            throw new HttpException(ERR_DEVICE_NON_EXISTS, "device-id not found");
        }

        $channel_id = $this->get_request_param("channel_id", "int", true);
        $policy = new RandomPolicy($this->getDi());
        $selected_news_list = $policy->sampling($channel_id, $this->deviceId, null, 
                                MAX_NEWS_SENT_COUNT);
        $ret = array();

        foreach ($selected_news_list as $selected_news) {
            $news_model = News::getBySign($selected_news);
            if ($news_model) {
                $ret []= $this->serializeNewsCell($news_model);
            }
        }

        $this->setJsonResponse($ret);
        return $this->response;
    }

    public function likeAction() {
        if (!$this->request->isPost()) {
            throw new HttpException(ERR_INVALID_METHOD, "not supported method");
        } 

        $req = $this->request->getJsonRawBody(true);
        if (null === $req) {
            throw new HttpException(ERR_BODY_ERR, "body format error");
        }

        $newsSign = $this->get_or_fail($req, "news_id", "string");
        $now = News::getBySign($newsSign, array("liked"));
        if (!$now) {
            throw new HttpException(ERR_NEWS_NON_EXISTS, "news $newsSign non exists");
        }

        $ret = $this->getDi()
                    ->getShared('db')->query(
                    "UPDATE tb_news 
                    SET liked = liked + 1 WHERE url_sign = '". mysql_real_escape_string($newsSign) . "'");
        if (!$ret) {
            throw new HttpException(ERR_INTERNAL_DB, "internal error");
        }
        
        $ret = array (
            "message" => "ok",
            "liked" => $now->liked + 1,
        );

        $this->setJsonResponse($ret);
        return $this->response;
    }


    protected function getRecommendNewsList($news_model) {
    
    }

   protected function serializeComment($comment){
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


    protected function serializeNewsCell($news_model) {
        $imgs = NewsImage::getImagesOfNews($news_model->url_sign);
        $commentCount = Comment::getCount($news_model->id);

        $ret = array (
            "title" => $news_model->title,
            "commentCount" => $commentCount,
            "news_id" => $news_model->url_sign,
            "source" => $news_model->source_name,
            "source_url" => $news_model->source_url,
            "public_time" => $news_model->publish_time,
        );

        $ret = array_merge($ret, ImageHelper::formatImageAndTpl($imgs));

        return $ret;
    }
}
