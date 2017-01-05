<?php
/**
 * 
 * @file    BannerIntervene.php
 * @authors Zhao Yulong (elysium.zyl@gmail.com)
 * @date    2016-12-04 13:07:07
 * @version $Id$
 */

use Phalcon\DI;

define("TOPIC_INTERVENE_KEY", "TOPIC_INTERVENE_");
define("TOPIC_INTERVENE_TTL", 86400);

class TopicIntervene extends BaseIntervene {
    public function __construct($context = array()) {
        parent::__construct($context);
        
        $topic_id = $this->select();
        if (!$topic_id {
            $this->empty = true;
        } else {
            $this->flagSign = TOPIC_INTERVENE_KEY . $topic_id;
        }
    }

    public function select() {
        $topics = Topic::getValidTopic();
        $cache = DI::getDefault()->get('cache');
        $key = TOPIC_INTERVENE_KEY . $this->context["device_id"];
        if ($cache) {
            foreach ($topics as $topic_id) {
                if($cache->sIsMember($key, $topic_id)){
                    continue;
                }
                return $topic_id;
            }
        }
        return null;
    }

    public function render() {
        if (!Features::Enabled(Features::TOPIC_FEATURE, 
            $this->context["client_version"], 
            $this->context["os"])) {
            return null;
        }

        $topic_id = $this->select();
        if(!$topic_id) {
            return null;
        }

        $device_id = $this->context["device_id"];
        $topic_model = Topic::GetByTopicId($topic_id);
        $ret = $this->serializeNewsCell($topic_model);
        $this->setDeviceUsed($topic_id, $device_id);
        return $ret;
    }

    protected function serializeNewsCell($topic_model) {
        $ret = array(
            "title" => $topic_model->title,
            "news_id" => $topic_model->topic_id,
            "public_time" => $topic_model->publish_time,
            "imgs" => array(),
            "tag" => "Topics",
            );

        $meta = json_decode($topic_model->image_meta, true);

        $ret["imgs"][] = RenderLib::LargeImageRender($this->context["net"],
            $topic_model->image_sign, $meta, $this->context["screen_w"],
            $this->context["screen_h"], $this->context["os"]);
        $ret["tpl"] = NEWS_LIST_TOPIC;
       
        return $ret;
    }

    protected function setDeviceUsed($topic_id, $device_id) {
        $cache = DI::getDefault()->get('cache');
        if (!$cache) {
            return;
        }
    
        $key = TOPIC_INTERVENE_KEY . $device_id;
        $cache->multi();
        $cache->sAdd($key, $topic_id);
        $cache->expire($key, TOPIC_INTERVENE_TTL);
        $cache->exec();
    }
}
