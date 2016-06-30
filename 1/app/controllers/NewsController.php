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

        $commentCount = Comment::getCount($newsSign);
        $topComment = Comment::getAll($newsSign, null, 3);

        $imgs = NewsImage::getImagesOfNews($newsSign);
        $imgcell = array();
        foreach ($imgs as $img) {
            if (!$img || $img->is_deadlink == 1 || !$img->meta) {
                continue;
            }

            if ($img->origin_url) {
                $meta = json_decode($img->meta, true);
                if (!$meta || !$meta["width"] || !$meta["height"]) {
                    continue;
                }
            }
    
            $ow = $meta["width"];
            $oh = $meta["height"];
            $aw = (int) ($this->resolution_w * 11 / 12);
            $ah = (int) min($this->resolution_h * 0.9, $aw * $oh / $ow);

            $imgcell[] = array(
                "src" => sprintf(DETAIL_IMAGE_PATTERN, $img->url_sign, $aw),
                "width" => $aw,
                "height" => $ah,
            );
        }

        $ret = array(
            "body" => $news_model->json_text,
            "commentCount" => $commentCount,
            "comments" => array(), 
            "imgs" => $imgcell, 
            "recommend_news" => array(),
            "news_id" => $news_model->url_sign,
            "title" => $news_model->title,
            "source" => $news_model->source_name,
            "source_url" => $news_model->source_url,
            "public_time" => $news_model->publish_time,
            "share_url" => sprintf(SHARE_TEMPLATE, urlencode($news_model->url_sign)),
            "channel_id" => $news_model->channel_id,
            "likedCount" => $news_model->liked,
            "collect_id" => 0, 
        );

        $recommend_selector = new BaseRecommendNewsSelector($news_model->channel_id, $this->getDI());
        $models = $recommend_selector->select($this->deviceId, $this->userSign, $news_model->url_sign);
        if (class_exists($cname)) {
            $render = new $cname($this->deviceId, $this->resolution_w, $this->resolution_h);
        } else {
            $render = new BaseListRender($this->deviceId, $this->resolution_w, $this->resolution_h);
        }

        $ret["recommend_news"][]= $render->render($models);
        if ($this->userSign) {
            $ret["collect_id"] = Collect::getCollectId($this->userSign, $newsSign);
        }

        foreach ($topComment as $comment) {
            array_push($ret["comments"], $this->serializeComment($comment));
        }

        // ----------------- pseduo like, this feature should be removed later -----------------
        $pseduoLike = mt_rand(1, 10);
        if ($pseduoLike == 1 && ($news_model->channel_id == 10004 || $news_model->channel_id == 10006)) {
            $news_model->liked++;
            $news_model->save();
            $this->logger->info(sprintf("[pseudo:%d]", $news_model->liked));
        }
        // ----------------- end -------TODO remove later---------------------------------------


        $this->logEvent(EVENT_NEWS_DETAIL, array(
                                               "news_id"=> $newsSign,
                                               "recommend"=> array(
                                                                   "news" => $recommend_news_list,
                                                                   "policy"=> "random",
                                                                   ),
                                                 ));
        
        $this->logger->info(sprintf("[Detail][news:%s][imgs:%d][channel:%d]", $newsSign, count($ret["imgs"]),
                                     $news_model->channel_id));
        
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
        $prefer = $this->get_request_param('dir', "string", false, "later");
        if (!($prefer == 'later' || $prefer == 'older')) {
            throw new HttpException(ERR_BODY, "'dir' error");
        }


        $cname = "Selector$channel_id";
        if (class_exists($cname)) {
            $selector = new $cname($channel_id, $this->getDI()); 
        } else {
            $selector = new BaseNewsSelector($channel_id, $this->getDI());
        }

        $models = $selector->select($this->deviceId, $this->userSign, $prefer);
        $dispatch_ids = array();
        foreach ($models as $sign => $model) {
            $dispatch_ids []= $sign;
        }

        $cname = "Render$channel_id";
        if (class_exists($cname)) {
            $render = new $cname($this->deviceId, $this->resolution_w, $this->resolution_h);
        } else {
            $render = new BaseListRender($this->deviceId, $this->resolution_w, $this->resolution_h);
        }

        $dispatch_id = substr(md5($prefer . $channel_id . $this->deviceId . time()), 16);
        $ret[$dispatch_id] = $render->render($models);

        $this->logger->info(sprintf("[List][dispatch_id:%s][policy:%s][pfer:%s][cnl:%d][sent:%d]",
                                    $dispatch_id, $selector->getPolicyTag(), $prefer, 
                                    $channel_id, count($ret[$dispatch_id])));

        $this->logEvent(EVENT_NEWS_LIST, array(
                                              "dispatch_id"=> $dispatch_id,
                                              "news"=> $dispatch_ids,
                                              "policy"=> $selector->getPolicyTag(),
                                              "channel_id" => $channel_id,
                                              "prefer" => $prefer,
                                              ));
        $this->setJsonResponse($ret);
        return $this->response;
    }

    public function likeAction() {
        if (!$this->request->isPost()) {
            throw new HttpException(ERR_INVALID_METHOD, "not supported method");
        } 

        $req = $this->request->getJsonRawBody(true);
        if (null === $req) {
            throw new HttpException(ERR_BODY, "body format error");
        }

        $newsSign = $this->get_or_fail($req, "news_id", "string");
        $now = News::getBySign($newsSign);
        if (!$now) {
            throw new HttpException(ERR_NEWS_NON_EXISTS, "news $newsSign non exists");
        }

        $now->liked++;
        $ret = $now->save();
        if (!$ret) {
            $this->logger->warning(sprintf("save error: %s", join(",",$now->getMessages())));
            throw new HttpException(ERR_INTERNAL_DB, "internal error");
        }

        $ret = array (
            "message" => "ok",
            "liked" => $now->liked,
        );

        $this->logger->info(sprintf("[Like][liked:%s]", $ret["liked"]));
        $this->logEvent(EVENT_NEWS_LIKE, array("news_id"=>$newsSign, "liked"=>$ret["liked"]));
        $this->setJsonResponse($ret);
        return $this->response;
    }


   protected function serializeComment($comment){
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
}
