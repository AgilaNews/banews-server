<?php
/**
 * 
 * @file    TopicController.php
 * @authors Zhao Yulong (elysium.zyl@gmail.com)
 * @date    2016-12-11 16:11:34
 * @version $Id$
 */

class TopicController extends BaseController {
    protected function formatTopic($topic) {
        $meta = json_decode($topic->image_meta, true);
        $ret = array(
            "title" => $topic->title,
            "public_time" => $topic->publish_time,
            "tags" => $topic->tags,
            "news_id" => $topic->topic_id,
            "is_valid" => $topic->is_valid,
            "count" => TopicNews::count(
                [
                    "topic_id = ?0",
                    "bind" => [
                        $topic->topic_id,
                        ],
                ]),
            "imgs" => RenderLib::ImageRender($this->net, $topic->image_sign, $meta, true),
            );
        $ret["imgs"]["image_sign"] = $topic->image_sign;
        return $ret;
    }

    public function DetailAction() {
        $topic_id = $this->get_request_param("news_id", "string", true);
        #$pn = $this->get_request_param("pn", "int", false, 20);
        $pn = 20;
        $from = $this->get_request_param("from", "int", false, 0);

        $ret = array(
            "news" => array(),
            );
        $topic = Topic::getByTopicId($topic_id);

        if ($topic) {
            $ret = $this->formatTopic($topic);
            $dispatch_id = substr(md5($topic_id . $this->deviceId . time()), 16);
            $ret["dispatch_id"] = $dispatch_id;

            $news = TopicNews::GetNewsOfTopic($topic_id, $from, $pn);
            if ($topic_id == TOPIC_FOR_MISS_AGILA) {
                $direction = array_shift($news);
                shuffle($news);
                array_unshift($news, $direction);
            }
            $ret["news"] = array();
            if ($news) {
                $models = News::BatchGet($news);
                $render = new RenderTopicNews($this);
                $ret["news"] = $render->render($models, $from);
            }
            $this->logger->info(sprintf("[Detail][Topic:%s][news:%d][dispatch_id:%s]",
                $topic_id, count($news), $dispatch_id));

            $redis = $this->di->get('cache');
            $cache = new NewsRedis($redis);
            $cache->setDeviceSeen($this->deviceId, $news);

            $this->logEvent(EVENT_TOPIC_DETAIL, array(
                                               "topic_id"=> $topic_id,
                                               "news"=> $news,
                                               "dispatch_id" => $dispatch_id,
                                                ));
        }
        $this->setJsonResponse($ret);
        return $this->response;
    }
}
