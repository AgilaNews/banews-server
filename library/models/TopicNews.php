<?php
/**
 * 
 * @file    TopicNews.php
 * @authors Zhao Yulong (elysium.zyl@gmail.com)
 * @date    2016-12-07 16:50:54
 * @version $Id$
 */

/*
CREATE TABLE `tb_topic_news` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'auto increment id',
  `topic_id` varchar(20) NOT NULL COMMENT 'topic id',
  `news_id` varchar(20) NOT NULL COMMENT 'news id',
  `index` tinyint(8) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_topic_id` (`topic_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='topic news table'
*/

use Phalcon\DI;

class TopicNews extends BaseModel {
    public $id;

    public $news_id;

    public $topic_id;

    public $index;

    public function getSource(){
        return "tb_topic_news";
    }

    public static function GetNewsOfTopic($topic_id, $start, $count) {
        $news = self::_getFromCache($topic_id, $start, $count);
        if ($news) {
            return $news;
        } else {
            $news = self::_getFromDB($topic_id);
            if ($news) {
                self::_saveToCache($topic_id, $news);
            }
            return self::_getFromCache($topic_id, $start, $count);
        }
    }

    protected static function _getFromCache($topic_id, $start, $count) {
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            $key = CACHE_TOPIC_NEWS_PREFIX . $topic_id;
            if($cache->exists($key)) {
                return $cache->lRange($key, $start, $count + $start - 1);
            }
        }
        return null;
    }

    protected static function _getFromDB($topic_id, $start=0, $limit=-1) {
        $crit = array(
            "conditions" => "topic_id = ?1",
            "bind" => array(1 => $topic_id),
            "order" => "index",
            "columns" => "news_id",
            "offset" => $start,
            );

        if($limit > 0) {
            $crit["limit"] = $limit;
        }

        $ret = array();
        $models = TopicNews::find($crit);
        foreach ($models as $model) {
            $ret[] = $model["news_id"];
        }
        return $ret;
    }

    protected static function _saveToCache($topic_id, $news){
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            $key = CACHE_TOPIC_NEWS_PREFIX . $topic_id;
            $cache->multi();
            $cache->delete($key);
            foreach ($news as $n) {
                $cache->rPush($key, $n);
            }
            $cache->expire($key, CACHE_TOPIC_TTL);
            $cache->exec();
        }
    }

    public static function GetTopicByNews($news_id) {
        $crit = array(
            "conditions" => "news_id = ?1",
            "bind" => array(1 => $news_id),
            "columns" => "topic_id",
            );
        return TopicNews::findFirst($crit);
    }

    public static function SaveNews($from, $newsList, $topic_id) {
        foreach ($newsList as $key => $news_id) {
            $crit = array(
                "conditions" => "news_id = ?1 and topic_id = ?2",
                "bind" => array(
                    1 => $news_id,
                    2 => $topic_id),
                );
            $model = TopicNews::findFirst($crit);
            if (!$model) {
                $model = new TopicNews();
                $model->news_id = $news_id;
                $model->topic_id = $topic_id;
            }

            $model->index = $key + $from;
            $model->save();
        }

        $ret["next"] = $from + count($newsList);
        $cache = DI::getDefault()->get('cache');
        $cache->delete(CACHE_TOPIC_NEWS_PREFIX . $topic_id);
        return $ret;
    }

    public static function AddNews($newsList, $topic_id) {
        $from = TopicNews::count(
            [
                "topic_id = ?0",
                "bind" => [
                    $topic_id,
                    ],
            ]
            );
        $ret = array(
            "news" => array()
            );
        foreach ($newsList as $key => $news_id) {
            $crit = array(
                "conditions" => "news_id = ?1 and topic_id = ?2",
                "bind" => array(
                    1 => $news_id,
                    2 => $topic_id),
                );
            $model = TopicNews::findFirst($crit);
            if (!$model) {
                $model = new TopicNews();
                $model->news_id = $news_id;
                $model->topic_id = $topic_id;
                $model->index = $from;
                $from = $from + 1;
                $model->save();
                $ret["news"][] = $news_id;
            }
        }
        $ret["next"] = $from;

        $cache = DI::getDefault()->get('cache');
        $cache->delete(CACHE_TOPIC_NEWS_PREFIX . $topic_id);
        return $ret;
    }

    public static function DeleteNews($newsList, $topic_id) {
        $ret = array(
            "news" => array()
            );
        foreach ($newsList as $key => $news_id) {
            $crit = array(
                "conditions" => "news_id = ?1 and topic_id = ?2",
                "bind" => array(
                    1 => $news_id,
                    2 => $topic_id),
                );
            $model = TopicNews::findFirst($crit);
            if ($model) {
                $model->delete();
                $ret["news"][] = $news_id;
            }
        }

        $cache = DI::getDefault()->get('cache');
        $cache->delete(CACHE_TOPIC_NEWS_PREFIX . $topic_id);
        return $ret;
    }
}