<?php
/**
 * 
 * @file    video.php
 * @authors Zhao Yulong (elysium.zyl@gmail.com)
 * @date    2016-10-27 21:51:06
 * @version $Id$
 */

use Phalcon\DI;
define('CACHE_VIDEO_PREFIX', "BS_VIDEO_");
define('CACHE_VIDEO_TTL', 14400);

class Video extends BaseModel {
    public $id;

    public $news_id;

    public $channel_id;

    public $news_url_sign;

    public $youtube_video_id;

    public $youtube_category_id;

    public $cover_origin_url;

    public $cover_save_url;

    public $cover_image_sign;

    public $cover_meta;

    public $youtube_channel_id;

    public $youtube_channel_name;

    public $description;

    public $title;

    public $duration;

    public $tags;

    public $origin_like;

    public $origin_favorite;

    public $origin_view;

    public $origin_comment;

    public $update_time;

    public $view;

    public $is_valid;

    public $status;

    public function getSource(){
        return "tb_video";
    }

    public static function getByNewsSign($sign) {
        $model = self::_getFromCache($sign);
        if ($model) {
            return $model;
        } else {
            $model = self::_getFromDB($sign);
            if ($model) {
                self::_saveToCache($model);
            }
            return $model;
        }
    }

    protected static function _getFromCache($sign) {
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            $key = CACHE_VIDEO_PREFIX . $sign;
            $value = $cache->get($key);
            if ($value) {
                $model = new Video();
                $model->unserialize($value);
                return $model;
            }
        }
        return null;
    }

    protected static function _saveToCache($model){
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            $key = CACHE_VIDEO_PREFIX . $model->news_url_sign;
            $cache->multi();
            $cache->set($key, $model->serialize());
            $cache->expire($key, CACHE_VIDEO_TTL);
            $cache->exec();
        }
    }

    protected static function _getFromDB($sign) {
        $crit = array ("conditions" => "news_url_sign = ?1 and status >= 0",
                       "bind" => array (1 => $sign),
                      );

        $video_model = Video::findFirst($crit);
        return $video_model;
    }

    protected static function _batchSaveToCache(array $models) {
        $cache = DI::getDefault()->get('cache');
        $cache->multi();

        foreach ($models as $sign => $model) {
            if (!$model) {
                continue;
            }
            $cache->set(CACHE_VIDEO_PREFIX . $model->news_url_sign, $model->serialize());
            $cache->expire(CACHE_VIDEO_PREFIX . $model->news_url_sign, CACHE_VIDEO_TTL);
        }

        $cache->exec();
        return;
    }

    protected static function _batchGetFromCache($signs) {
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            $keys = array();
            foreach ($signs as $sign) {
                $keys []= CACHE_VIDEO_PREFIX . $sign;
            }
            $rret = $cache->mget($keys);
            if (!$rret) {
                return array();
            }

            $ret = array();
            foreach ($rret as $idx => $value) {
                if ($value) {
                    $model = new News();
                    $model->unserialize($value);
                    $ret[$signs[$idx]] = $model;
                } else {
                    $ret[$signs[$idx]]= null;
                }
            }
            return $ret;
        }
        return array();
    }

    protected static function _batchGetFromDB($signs) {
        if (count($signs) == 0) {
            return array();
        }

        $crit = array("conditions" => "news_url_sign IN ({signs:array}) and status >= 0",
                      "bind" => array("signs" => $signs));

        $ret = Video::find($crit);
        $models = array();
        foreach ($ret as $model) {
            $models[$model->news_url_sign] = $model;
        }
        self::_batchSaveToCache($models);
        return $models;
    }

    public static function batchGet(array $signs) {
        $ret = self::_batchGetFromCache($signs);
        if (!$ret) {
            return self::_batchGetFromDB($signs);
        }

        $left = array();
        foreach ($ret as $sign => $model) {
            if (!$model) {
                $left []= $sign;
            }
        }
        
        if (count($left) != 0) {
            $left_models = self::_batchGetFromDB($left);
            $ret = array_merge($ret, $left_models);
        }
        return $ret;
    }

    public function save($data = null, $whitelist = null) {
        $ret = parent::save($data, $whitelist);
        if ($ret) {
            Video::_saveToCache($this);
        }
        return $ret;
    }

    public static function getVideosByAuthor($youtube_channel_id, $pn) {
        $ret = self::_getVideosByAuthorFromCache($youtube_channel_id, $pn);
        if (!$ret) {
            $ret = self::_getVideosByAuthorFromDB($youtube_channel_id, $pn);
            self::_saveVideosByAuthorToCache($youtube_channel_id, $ret);
        }
        shuffle($ret);
        return array_slice($ret, 0, $pn);
    }
    protected static function _getVideosByAuthorFromCache($youtube_channel_id, $pn) {
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            $key = CACHE_CHANNEL_VIDEO_PREFIX . $youtube_channel_id;
            $ret = $cache->lrange($key, 0, -1);
            return $ret;
        }
        return null;
    }
    protected static function _getVideosByAuthorFromDB($youtube_channel_id, $pn) {
        $crit = array(
            "conditions" => "youtube_channel_id = ?1 and is_valid = 1 and status=1",
            "bind" => array(
                1 => $youtube_channel_id
                ),
            );
        $video_models = Video::find($crit);
        $ret = array();
        foreach ($video_models as $video_model) {
            $ret[] = $video_model->news_url_sign;
        }
        return $ret;
    }
    protected static function _saveVideosByAuthorToCache($youtube_channel_id, $videos) {
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            $key = CACHE_CHANNEL_VIDEO_PREFIX . $youtube_channel_id;
            $cache->multi();
            $cache->delete($key);
            foreach($videos as $video) {
                $cache->lpush($key, $video);
            }
            $cache->expire($key, CACHE_CHANNEL_VIDEO_TTL);
            $cache->exec();
        }
    }
}
}