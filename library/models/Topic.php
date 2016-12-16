<?php
/**
 * 
 * @file    Topic.php
 * @authors Zhao Yulong (elysium.zyl@gmail.com)
 * @date    2016-12-07 16:50:03
 * @version $Id$
 */

/*
CREATE TABLE `tb_topic` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'auto increment id',
  `topic_id` varchar(20) NOT NULL COMMENT 'topic id',
  `channel_id` int(11) NOT NULL DEFAULT '0' COMMENT 'channel',
  `image_save_url` varchar(1024) NOT NULL COMMENT 'cover image saved url',
  `image_meta` longtext COMMENT 'cover meta information',
  `image_sign` varchar(20) NOT NULL,
  `json_text` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'topic content',
  `title` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tags` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'tags',
  `is_valid` tinyint(1) NOT NULL DEFAULT '0',
  `update_time` bigint(20) NOT NULL COMMENT 'update timestamp',
  `publish_time` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_topic_id` (`topic_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='topic table';
*/

use Phalcon\DI;

define("CACHE_VALID_TOPIC", "BS_VALID_TOPICS");

class Topic extends BaseModel {
    public $id;

    public $topic_id;

    public $channel_id;

    public $image_save_url;

    public $image_meta;

    public $image_sign;

    public $title;

    public $json_text;

    public $tags;

    public $is_valid;

    public $update_time;

    public $public_time;

    public function getSource(){
        return "tb_topic";
    }

    public static function BatchGet($start, $count) {
        $crit = array(
            "limit" => $count,
            "offset" => $start,
            "order" => "id desc",
            );
        return Topic::find($crit);
    }

    public static function GetByTopicId($topic_id) {
        $model = self::_getFromCache($topic_id);
        if ($model) {
            return $model;
        } else {
            $model = self::_getFromDB($topic_id);
            if ($model) {
                self::_saveToCache($model);
            }
            return $model;
        }
    }

    protected static function _getFromCache($topic_id) {
        $cache = DI::getDefault()->get('cache');
        if($cache) {
            $key = CACHE_TOPIC_REPFIX . $topic_id;
            $value = $cache->get($key);
            if($value) {
                $model = new Topic();
                $model->unserialize($value);
                return $model;
            }
        }
        return null;
    }

    protected static function _getFromDB($topic_id) {
        $crit = array(
            "conditions" => "topic_id = ?1",
            "bind" => array(1 => $topic_id)
            );
        return Topic::findFirst($crit);
    }

    protected static function _saveToCache($model){
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            $key = CACHE_TOPIC_REPFIX . $model->topic_id;
            $cache->multi();
            $cache->set($key, $model->serialize());
            $cache->expire($key, CACHE_TOPIC_TTL);
            $cache->exec();
        }
    }

    public function save($data = null, $whitelist = null) {
        $ret = parent::save($data, $whitelist);
        if ($ret) {
            Topic::_saveToCache($this);
        }
        return $ret;
    }

    public static function getValidTopic() {
        $topics = self::_getValidTopicFromCache();
        if ($topics) {
            return $topics;
        } else {
            $topics = self::_getValidTopicFromDB();
            if ($topics) {
                self::_saveValidTopicToCache($topics);
            }
            return $topics;
        }
    }

    public static function _getValidTopicFromCache() {
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            $key = CACHE_VALID_TOPIC;
            $count = $cache->sCard($key);
            return $cache->sRandMember($key, $count);
        }
        return null;
    }

    public static function _getValidTopicFromDB() {
        $crit = array(
            "conditions" => "is_valid = 1",
            "columns" => "topic_id"
            );
        $models = Topic::find($crit);
        $ret = array();
        foreach ($models as $model) {
            $ret[] = $model["topic_id"];
        }
        return $ret;
    }

    public static function _saveValidTopicToCache($topics) {
        $cache = DI::getDefault()->get('cache');
        if($cache) {
            foreach ($topics as $topic_id) {
                $cache->sAdd($key, $topic_id);
            }
        }
    }

    public static function SetTopicValid($topic_id, $isValid) {
        $topic = self::GetByTopicId($topic_id);
        $topic->is_valid = $isValid;
        $topic->save();
        $topics = self::_getValidTopicFromDB();
        if ($topics) {
            self::_saveValidTopicToCache($topics);
        }
    }
}