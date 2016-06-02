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

define('MIN_NEWS_SEND_COUNT', 8);
define('MAX_NEWS_SENT_COUNT', 12);

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

        $ret = array(
            "body" => $news_model->json_text,
            "commentCount" => $commentCount,
            "comments" => array(), 
            "imgs" => ImageHelper::formatImgs($imgs, $this->deviceModel, false),
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

        $recommend_policy = new RandomRecommendPolicy($this->getDi());
        $recommend_news_list =
            $recommend_policy->sampling($news_model->channel_id,
                                        $this->deviceId, null, 4);

        $recommend_models = News::batchGet($recommend_news_list);
        foreach ($recommend_models as $recommend_model) {
            if ($recommend_model->url_sign == $newsSign) {
                continue;
            }
            $ret["recommend_news"][]= $this->serializeNewsCell($recommend_model);
            if (count($ret["recommend_news"]) == 3) {
                break;
            }
        }

        if ($this->userSign) {
            $ret["collect_id"] = Collect::getCollectId($this->userSign, $newsSign);
        }

        foreach ($topComment as $comment) {
            array_push($ret["comments"], $this->serializeComment($comment));
        }

        $this->logEvent(EVENT_NEWS_DETAIL, array(
                                               "news_id"=> $newsSign,
                                               "recommend"=> array(
                                                                   "news" => $recommend_news_list,
                                                                   "policy"=> "random",
                                                                   ),
                                                 ));
        
        $this->logger->info(sprintf("[Detail][news:%s][imgs:%d]", $newsSign, count($ret["imgs"])));
        
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
        $policy = new ExpDecayListPolicy($this->getDi());
        $prefer = $this->get_request_param('dir', "string", false, "later");

        if (!($prefer == 'later' || $prefer == 'older')) {
            throw new HttpException(ERR_BODY, "'dir' error");
        }

        $required = mt_rand(MIN_NEWS_SEND_COUNT, MAX_NEWS_SENT_COUNT);
        $base = round(MAX_NEWS_SENT_COUNT * 1.5);
        $selected_news_list = $policy->sampling($channel_id, $this->deviceId, null, $base,
                                                $prefer);
        $dispatch_id = substr(md5($prefer . $channel_id . $this->deviceId . time()), 16);
        $ret = array($dispatch_id => array());
    
        $dispatched = array();
        $uniq = array();

        $models = News::batchGet($selected_news_list);
        foreach ($models as $sign => $news_model) {
            if ($news_model && $news_model->is_visible == 1) {
                if (array_key_exists($news_model->content_sign, $uniq) && 
                    $uniq[$news_model->content_sign]->source_name == $news_model->source_name) {
                    //content sign dup and same source, continue
                    continue;
                }

                $ret [$dispatch_id][] = $this->serializeNewsCell($news_model);
                $dispatched []= $sign;
                $uniq[$news_model->content_sign] = $news_model;
            }

            if (count($dispatched) >= $required) {
                break;
            }
        }

        $this->logger->info(sprintf("[List][dispatch_id:%s][policy:expdecay][pfer:%s][cnl:%d][sent:%d]",
                                    $dispatch_id, $prefer, $channel_id, count($dispatched)));
        $policy->setDeviceSent($this->deviceId, $dispatched);
        $this->logEvent(EVENT_NEWS_LIST, array(
                                              "dispatch_id"=> $dispatch_id,
                                              "news"=> $dispatched,
                                              "policy"=> "expdecay", //we just have one policy
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
            $this->logger->warning("save error: %s", $now->getMessages());
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
        
        $user_model = User::getBySign($comment->news_sign);
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

        $ret = array_merge($ret, ImageHelper::formatImageAndTpl($imgs, $this->deviceModel, true));
 
        return $ret;
    }
}
