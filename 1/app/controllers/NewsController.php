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

    private $featureChannelLst = array(10001);

    public function DetailAction() {
        if (!$this->request->isGet()){
            throw new HttpException(ERR_INVALID_METHOD,
                "read news must be get");
        }
        if (!$this->deviceId) {
            throw new HttpException(ERR_DEVICE_NON_EXISTS, "device-id not found");
        }

        $newsSign = $this->get_request_param("news_id", "string", true);
        $news_model = News::getBySign($newsSign);
        if (!$news_model) {
            throw new HttpException(ERR_NEWS_NON_EXISTS, "news not found");
        }

        $commentCount = Comment::getCount(array($newsSign));

        $cache = $this->di->get("cache");
        $redis = new NewsRedis($cache);
        $redis->setDeviceClick(
                               $this->deviceId, $newsSign, time()); 

        $ret = array(
            "body" => $news_model->json_text,
            "commentCount" => $commentCount[$newsSign],
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

        $topNewComment = Comment::getCommentByFilter($this->deviceId, $newsSign, 0, 5, "new");

        if (Features::Enabled(Features::RICH_COMMENT_FEATURE, $this->client_version, $this->os)) {
            $topHotComment = Comment::getCommentByFilter($this->deviceId, $newsSign, 0, 3, "hot");
            $ret["comments"] = array(
                                      "new" => $topNewComment,
                                      "hot" => $topHotComment,
                                      );
        } else {
            $ret["comments"] = $topNewComment;
        }

        $imgs = NewsImage::getImagesOfNews($newsSign);
        
        $videocell = array();
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
    
            $c = $this->getImgCell($img->url_sign, $meta);
            $c["name"] = "<!--IMG" . $img->news_pos_id . "-->";
            $imgcell[] = $c;
        }
        $ret["imgs"] = $imgcell;

        if (Features::Enabled(Features::VIDEO_NEWS_FEATURE, $this->client_version, $this->os)) {
            $videos = NewsYoutubeVideo::getVideosOfNews($newsSign);
            foreach($videos as $video) {
                if (!$video || $video->is_deadlink == 1 || !$video->cover_meta) {
                    continue;
                }
                
                if ($video->cover_origin_url) {
                    $cover_meta = json_decode($video->cover_meta, true);
                    if (!$cover_meta || !$cover_meta["width"] || !$cover_meta["height"]) {
                        continue;
                    }
                }

                $c = $this->getImgCell($video->video_url_sign, $cover_meta);
                $c["video_pattern"] = $c["pattern"] . "|v=1";
                $c["youtube_id"] = $video->youtube_video_id;
                $c["name"] = "<!--YOUTUBE" . $video->news_pos_id . "-->";
                $videocell []= $c;
            }

            $ret["youtube_videos"] = $videocell;
        }

        
        $recommend_selector = new BaseRecommendNewsSelector($news_model->channel_id, $this);
        $models = $recommend_selector->select($news_model->url_sign);
        $cname = "Recommend" . $news_model->channel_id;
        if (class_exists($cname)) {
            $render = new $cname($this, $news_model->channel_id);
        } else {
            $render = new BaseListRender($this, $news_model->channel_id);
        }

        $ret["recommend_news"]= $render->render($models);
        if ($this->userSign) {
            $ret["collect_id"] = Collect::getCollectId($this->userSign, $newsSign);
        }

        // ----------------- pseduo like, this feature should be removed later -----------------
        $pseduoLike = mt_rand(1, 5);
        if ($pseduoLike == 1 && ($news_model->channel_id == 10004 || $news_model->channel_id == 10006)) {
            $news_model->liked++;
            if (!$news_model->save()){
                $this->logger->warning(sprintf("save error: %s", join(",",$news_model->getMessages())));
            }
            $this->logger->info(sprintf("[pseudo:%d]", $news_model->liked));
        }
        // ----------------- end -------TODO remove later---------------------------------------

        $this->logEvent(EVENT_NEWS_DETAIL, array(
                                               "news_id"=> $newsSign,
                                               "recommend"=> array(
                                                                   "news" => array_keys($models),
                                                                   "policy"=> "random",
                                                                   ),
                                                 ));
        
        $this->logger->info(sprintf("[Detail][news:%s][imgs:%d][channel:%d][recommend:%d]", $newsSign, count($ret["imgs"]),
                                     $news_model->channel_id, count($ret["recommend_news"])));
        
        $this->setJsonResponse($ret);
        News::saveActionToCache($newsSign, 
                                CACHE_FEATURE_CLICK_PREFIX,
                                CACHE_FEATURE_CLICK_TTL);
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
        $dispatch_ids = array();
        if (!($prefer == 'later' || $prefer == 'older')) {
            throw new HttpException(ERR_BODY, "'dir' error");
        }

        $selector = BaseNewsSelector::getSelector($channel_id, $this);

        $newsFeatureDct = array();
        if (in_array($channel_id, $this->featureChannelLst)) {
            list($dispatch_models, $newsFeatureDct) = 
                $selector->select($prefer);
        } else {
            $dispatch_models = $selector->select($prefer);
        }
        $dispatch_ids = array();
        foreach ($dispatch_models as $dispatch_model) {
            if (isset($dispatch_model->url_sign)) {
                $dispatch_ids []= $dispatch_model->url_sign;
            }
        }
        
        News::batchSaveActionToCache($dispatch_ids, 
                                     CACHE_FEATURE_DISPLAY_PREFIX, 
                                     CACHE_FEATURE_DISPLAY_TTL);

        $cname = "Render$channel_id";
        if (class_exists($cname)) {
            $render = new $cname($this);
        } else {
            $render = new BaseListRender($this);
        }

        $dispatch_id = substr(md5($prefer . $channel_id . $this->deviceId . time()), 16);
        $ret[$dispatch_id] = $render->render($dispatch_models);

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

        if (in_array($channel_id, $this->featureChannelLst)) {
            foreach ($dispatch_ids as $newsId) {
                if (array_key_exists($newsId, $newsFeatureDct)) {
                    $param = array();
                    $param['news_id'] = $newsId;
                    $param['features'] = json_encode($newsFeatureDct[$newsId]);
                    $this->logFeature($dispatch_id, $param);
                }
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
            throw new HttpException(ERR_BODY, "body format error");
        }

        $newsSign = $this->get_or_fail($req, "news_id", "string");
        $now = News::getBySign($newsSign);
        if (!$now) {
            throw new HttpException(ERR_NEWS_NON_EXISTS, "news $newsSign non exists");
        }

        if (!isset($now->content_sign) || $now->content_sign == null || count($now->content_sign) == 0) {
            $now->content_sign = "";
        } 

        
        $originLike = $now->liked;
        $pseudoLike = mt_rand(1, 10);
        if ($pseudoLike == 1) {
            $now->liked += 2;
            $this->logger->info(sprintf("[pseudo:%d]", $now->liked));
        } else {
            $now->liked += 1;
        }

        $ret = $now->save();
        if (!$ret) {
            $this->logger->warning(sprintf("save error: %s", join(",",$now->getMessages())));
            throw new HttpException(ERR_INTERNAL_DB, "internal error");
        }

        $ret = array (
            "message" => "ok",
            "liked" => $originLike + 1,
        );

        $this->logger->info(sprintf("[Like][liked:%s]", $ret["liked"]));
        $this->logEvent(EVENT_NEWS_LIKE, array("news_id"=>$newsSign, "liked"=>$ret["liked"]));
        $this->setJsonResponse($ret);
        return $this->response;
    }


   protected function getImgCell($url_sign, $meta) {
       if ($this->net == "WIFI") {
            $quality = IMAGE_HIGH_QUALITY;
        } else if ($this->net == "2G") {
            $quality = IMAGE_LOW_QUALITY;
        } else {
            $quality = IMAGE_NORMAL_QUALITY;
        }
        
               
       $ow = $meta["width"];
       $oh = $meta["height"];
       $aw = (int) ($this->resolution_w * 11 / 12);
       $ah = (int) ($aw * $oh / $ow);

       return array(
                    "src" => sprintf(DETAIL_IMAGE_PATTERN, urlencode($url_sign), $aw, "", $quality),
                    "pattern" => sprintf(DETAIL_IMAGE_PATTERN, urlencode($url_sign), "{w}", "", $quality),
                    "width" => $aw,
                    "height" => $ah,
                    );
   }
}
