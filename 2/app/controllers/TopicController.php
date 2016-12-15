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
        return array(
            "title" => $topic->title,
            "publish_time" => $topic->publish_time,
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
    }

    public function DetailAction() {
        $topic_id = $this->get_request_param("news_id", "string", true);
        $pn = $this->get_request_param("pn", "int", false, 20);
        $from = $this->get_request_param("from", "int", false, 0);

        $ret = array(
            "news" => array(),
            );
        $topic = Topic::getByTopicId($topic_id);
        if ($topic) {
            $ret = $this->formatTopic($topic);

            $news = TopicNews::GetNewsOfTopic($topic_id, $from, $pn);
            if ($news) {
                $models = News::BatchGet($news);
                $render = new RenderTopicNews($this);
                $ret["news"] = $render->render($models);
            }
            $this->logger->info(sprintf("[Detail][Topic:%s][news:%d]", $topic_id, count($news)));

            $this->logEvent(EVENT_TOPIC_DETAIL, array(
                                               "topic_id"=> $topic_id,
                                               "news"=> $news,
                                                ));
        }
        $this->setJsonResponse($ret);
        return $this->response;
    }
}
