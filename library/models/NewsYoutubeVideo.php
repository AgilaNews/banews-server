<?php

use Phalcon\DI;

class NewsYoutubeVideo extends BaseModel {
    public $id;

    public $news_id;

    public $news_url_sign;

    public $news_pos_id;

    public $youtube_video_id;

    public $video_url_sign;

    public $cover_origin_url;

    public $cover_save_url;

    public $update_time;

    public $is_deadline;

    public $cover_meta;

    public $meta;

    public function getSource(){
        return "tb_news_youtube_video";
    }

    public static function getVideosOfNews($news_sign) {
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            $value = $cache->get(CACHE_YOUTUBE_VIDEO_PREFIX . $news_sign);
            if ($value) {
                $rs = unserialize($value);
                return $rs;
            }
        }

        $key = CACHE_YOUTUBE_VIDEO_PREFIX . $news_sign;
        $crit = array(
                      "conditions" => "news_url_sign=?1",
                      "bind" => array(1 => $news_sign),
                      );
        
        $rs = NewsYoutubeVideo::find($crit);
        if ($cache) {
            $cache->multi();
            $cache->set($key, serialize($rs));
            $cache->expire($key, CACHE_YOUTUBE_VIDEO_TTL);
            $cache->exec();
        }

        return $rs;
    }

    public static function batchGetVideosOfMultNews($newsIdLst) {
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            $keys = array();
            foreach ($newsIdLst as $newsId) {
                $keys[] = CACHE_YOUTUBE_VIDEO_PREFIX . $newsId;
            }
            $newsArr = $cache->mGet($keys);
            if (empty($newsArr)) {
                return array();
            } else  {
                $ret = array();
                foreach ($newsArr as $idx => $value) {
                    $newsId = $newsIdLst[$idx];
                    if (empty($value)) {
                        $ret[$newsId] = NewsYoutubeVideo::getVideosOfNews($newsId); 
                    } else {
                        $ret[$newsId] = unserialize($value);
                    }
                }
                return $ret;
            }
        }
        return array();
    }
}
