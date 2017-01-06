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
    const HotVideNum = 1;
    const VideoChannel = "30001";
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

        $cache = $this->di->get("cache");
        $redis = new NewsRedis($cache);
        $redis->setDeviceClick(
                               $this->deviceId, $newsSign, time()); 

        $render = BaseDetailRender::getRenderByChannel($news_model->url_sign);
        $ret = $render->render();

        $this->logEvent(EVENT_NEWS_DETAIL, array(
                                               "news_id"=> $newsSign,
                                               "recommend"=> array(
                                                                   "news" => array_keys($models),
                                                                   "policy"=> "random",
                                                                   ),
                                               "ad" => $ret["ad"],
                                                 ));
        
        $this->logger->info(sprintf("[Detail][news:%s][imgs:%d][channel:%d][recommend:%d]", $newsSign, count($ret["imgs"]),
                                     $news_model->channel_id, count($ret["recommend_news"])));
        
        $this->setJsonResponse($ret);
        $isLrRanker = $cache->get(ALG_LR_SWITCH_KEY);
        if ($isLrRanker) {
            News::saveActionToCache($newsSign, 
                CACHE_FEATURE_CLICK_PREFIX,
                CACHE_FEATURE_CLICK_TTL);
        }
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
            $selector = new $cname($channel_id, $this); 
        } else {
            $selector = new BaseNewsSelector($channel_id, $this);
        }

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
        $cache = $this->di->get("cache");
        $isLrRanker = $cache->get(ALG_LR_SWITCH_KEY);
        if ($isLrRanker) {
            News::batchSaveActionToCache($dispatch_ids, 
                CACHE_FEATURE_DISPLAY_PREFIX, 
                CACHE_FEATURE_DISPLAY_TTL);
        }
        
        $cname = "Render$channel_id";
        if (class_exists($cname)) {
            $render = new $cname($this);
        } else {
            $render = new BaseListRender($this);
        }

        $dispatch_id = substr(md5($prefer . $channel_id . $this->deviceId . time()), 16);
        
        if (Features::Enabled(Features::AB_FLAG_FEATURE, $this->client_version, $this->os)) {
            $ret = array(
                "dispatch_id" => $dispatch_id,
                "news" => $render->render($dispatch_models),
                "abflag" => json_encode($this->abflags),
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
        if (in_array($channel_id, $this->featureChannelLst) and $isLrRanker) {
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
}
