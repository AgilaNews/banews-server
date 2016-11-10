<?php
/**
 * 
 * @file    NewsController.php
 * @authors Zhao Yulong (elysium.zyl@gmail.com)
 * @date    2016-10-27 17:15:30
 * @version $Id$
 */

define ("VIDEO_CHANNEL_ID", 30001);
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
        $this->addView($newsSign);
        $cache = $this->di->get("cache");
        if (!$cache) {
            throw new HttpException(ERR_INTERNAL_DB, "cache error");
            
        }
        $redis = new NewsRedis($cache);
        $redis->setDeviceClick(
                               $this->deviceId, $newsSign, time()); 
        $ret = $this->getPublic($newsSign, $news_model);
        $ret["imgs"] = $this->getImgs($newsSign, $news_model->channel_id);
        $ret["videos"] = $this->getVideos($newsSign, $news_model->channel_id);
        $ret["tpl"] = $this->getTPL($news_model->channel_id);
        $this->logEvent(EVENT_NEWS_DETAIL, array(
                                               "news_id"=> $newsSign
                                            ));
        $this->setJsonResponse($ret);
        return $this->response;
    }

    private function getTPL($channel_id) {
        if ($channel_id == 30001) {
            return 12;
        } else {
            return 5;
        }
    }

    private function getPublic($newsSign, $news_model) {
        $commentCount = Comment::getCount(array($newsSign));

        $ret = array(
            "body" => $news_model->json_text,
            "commentCount" => $commentCount[$newsSign],
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

        if ($this->userSign) {
            $ret["collect_id"] = Collect::getCollectId($this->userSign, $newsSign);
        }
        return $ret;
    }

    private function getImgs($newsSign, $channel_id) {
        $imgs = array();
        if ($channel_id != 30001) {
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
        
                $c = $this->getImgCell($img->url_sign, $meta);
                $c["name"] = "<!--IMG" . $img->news_pos_id . "-->";
                $imgcell[] = $c;
            }
        } else {
            $video = Video::getByNewsSign($newsSign);
            $imgcell[] = $this->getImgCell(
                $video->cover_image_sign,
                json_decode($video->cover_meta, true));
        }

        return $imgcell;
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

       if ($this->os == "ios") {
           $aw = (int) ($this->resolution_w  - 44);
       } else {
           $aw = (int) ($this->resolution_w * 11 / 12);
       }

       $ah = (int) ($aw * $oh / $ow);

       return array(
                    "src" => sprintf(DETAIL_IMAGE_PATTERN, urlencode($url_sign), $aw, $quality),
                    "pattern" => sprintf(DETAIL_IMAGE_PATTERN, urlencode($url_sign), "{w}", $quality),
                    "width" => $aw,
                    "height" => $ah,
                    );
   }

    private function getVideos($newsSign, $channel_id) {
        $videocell = array();
        if ($channel_id != 30001) {
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
        } else {
            $video = Video::getByNewsSign($newsSign);
            $videocell [] = array(
                "youtube_id" => $video->youtube_video_id,
                //"width" => $aw,
                //"height" => $ah,
                "duration" => $video->duration,
                "description" => $video->description,
                "display" => 0
            );
        }
        return $videocell;
    }

    public function ListAction() {
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

        $cname = "Selector$channel_id";
        if (class_exists($cname)) {
            $selector = new $cname($channel_id, $this); 
        } else {
            $selector = new BaseNewsSelector($channel_id, $this);
        }

        $dispatch_models = $selector->select($prefer);
        
        foreach ($dispatch_models as $dispatch_model) {
            if (isset($dispatch_model->url_sign)) {
                $dispatch_ids []= $dispatch_model->url_sign;
            }
        }

        $cname = "Render$channel_id";
        if (class_exists($cname)) {
            $render = new $cname($this);
        } else {
            $render = new BaseListRender($this);
        }

        $dispatch_id = substr(md5($prefer . $channel_id . $this->deviceId . time()), 16);
        if (version_compare($this->client_version, "1.2.4", ">=")) {
            $ret = array(
                "dispatch_id" => $dispatch_id,
                "news" => $render->render($dispatch_models),
                "abflag" => json_encode(array()),
            );
            if (in_array($channel_id, array(10001))) {
                $ret["has_ad"] = 1;
            }
        } else { 
            $ret[$dispatch_id] = $render->render($dispatch_models);
        }

        $this->logger->info(sprintf("[List][dispatch_id:%s][policy:%s][pfer:%s][cnl:%d][sent:%d]",
                                    $dispatch_id, $selector->getPolicyTag(), $prefer, 
                                    $channel_id, count($dispatch_models)));

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

    public function LikeAction() {
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

    public function RecommendAction() {
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

        $recommend_selector = new BaseRecommendNewsSelector($news_model->channel_id, $this);
        $models = $recommend_selector->select($news_model->url_sign);
        $cname = "RecommendRender" . $news_model->channel_id;
        if (class_exists($cname)) {
            $render = new $cname($this);
        } else {
            $render = new BaseListRender($this);
        }

        $ret["recommend_news"]= $render->render($models);
        $this->logEvent(EVENT_NEWS_RECOMMEND, array(
                                                    "recommend"=> array(
                                                                   "news" => array_keys($models),
                                                                   "policy"=> "random",
                                                                   ),
                                                    "ad" => ""
                                               ));
        $this->logger->info(sprintf("[Recommend][policy:%s][cnl:%d][sent:%d]",
                                    $recommend_selector->getPolicyTag(), 
                                    $news_model->channel_id, count($ret["recommend_news"])));
        $this->setJsonResponse($ret);
        return $this->response;
    }


    private function addView($newsSign) {
        $video_model = Video::getByNewsSign($newsSign);
        if (!$video_model) {
            throw new HttpException(ERR_NEWS_NON_EXISTS, "news $newsSign non exists");
        }

        if (!$video_model) {
            return 0;
        }

        if (!isset($video_model->content_sign) || $video_model->content_sign == null || count($video_model->content_sign) == 0) {
            $video_model->content_sign = "";
        } 
        
        $originView = $video_model->view;
        $video_model->view += 1;

        $ret = $video_model->save();
        if (!$ret) {
            $this->logger->warning(sprintf("save error: %s", join(",",$video_model->getMessages())));
            throw new HttpException(ERR_INTERNAL_DB, "internal error");
        }
        return $originView;
    }
}
