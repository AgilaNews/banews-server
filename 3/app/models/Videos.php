<?php
/**
 * 
 * @file    videos.php
 * @authors Zhao Yulong (elysium.zyl@gmail.com)
 * @date    2016-10-27 21:51:06
 * @version $Id$
 */

class Videos extends BaseModel {
    public $id;

    public $news_id;

    public $channel_id;

    public $news_url_sign;

    public $youtube_video_id;

    public $youtube_category_id;

    public $cover_origin_url;

    public $cover_save_url;

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

    public $update_time;

    public function getSource(){
        return "tb_news";
    }

    public static function getByNewsSign($sign) {
        $model = self::_getFromCache($sign);
        if ($model) {
            return $model;
        } else {
            $model =  self::_getFromDB($sign);
            if ($model) {
                self::_saveToCache($model);
            }
            return $model;
        }
    }

    protected static function _getFromCache($sign) {
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            $key = CACHE_VIDEOS_PREFIX . $sign;
            $value = $cache->get($key);
            if ($value) {
                $model = new Videos();
                $model->unserialize($value);
                return $model;
            }
        }
        return null;
    }

    protected static function _saveToCache($model){
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            $key = CACHE_VIDEOS_PREFIX . $model->news_url_sign;
            $cache->multi();
            $cache->set($key, $model->serialize());
            $cache->expire($key, CACHE_VIDEOS_TTL);
            $cache->exec();
        }
    }

    protected static function _getFromDB($sign) {
        $crit = array ("conditions" => "news_url_sign = ?1",
                       "bind" => array (1 => $sign),
                      );

        $news_model = Videos::findFirst($crit);
        return $news_model;
    }
}