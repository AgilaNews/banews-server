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
        return array(
            "title" => $topic->title,
            "text" => $topic->json_text,
            "publish_time" => $topic->publish_time,
            "image_url" => sprintf(VIDEO_COVER_PATTERN, urlencode($topic->image_sign)),
            "tags" => $topic->tags,
            "topic_id" => $topic->topic_id,
            "is_valid" => $topic->is_valid,
            );
    }

    public function DetailAction() {
        $topic_id = $this->get_request_param("news_id", "string", true);
        $pn = $this->get_request_param("pn", "int", false, 20);
        $last_id = $this->get_request_param("last_id", "int", false, 0);
        $topic = Topic::getByTopicId($topic_id);
        $ret = $this->formatTopic($topic);

        $news = TopicNews::GetNewsOfTopic($topic_id, $last_id, $pn);
        $models = News::BatchGet($news);
        $render = new Render10001($this);
        $ret["news"] = $render->render($models);
        $this->setJsonResponse($ret);
        return $this->response;
    }
}
