<?php
define("VIDEO_LENGTH", 3000);
define("TOP_VIDEO_LENGTH", 1000);

class NewsRedis {
    private $image_gif_channel = array("10011", "10012");
    private $video_channel = array("30001");

    public function __construct($redis) {
        $this->_redis = $redis;
    }

    public function getVideos($channel_id) {
        $key = "banews:ph:$channel_id";
        $ret = array();
        $top = $this->_redis->zRevRange($key, 0, TOP_VIDEO_LENGTH - 1, true);

        foreach ($top as $id=>$weight) {
            $ret []= array("id" => $id, "weight"=>$weight);
        }

        $other = $this->_redis->zRevRange($key, TOP_VIDEO_LENGTH, -1, true);
        shuffle($other);

        $less = array_slice($other, 0, VIDEO_LENGTH - TOP_VIDEO_LENGTH - 1);
        foreach ($less as $id=>$weight) {
            $ret []= array("id" => $id, "weight"=>$weight);
        }

        return $ret;
    }
    
    public function getNewsOfChannel($channel_id, $day) {
        if (in_array($channel_id, self::$image_gif_channel)) {
            $key = "banews:ph:v2:$channel_id";
        } else if (in_array($channel_id, self::$video_channel)) {
            return $this->getVideos($channel_id);
        } else {
            $key = "banews:ph:$channel_id";
        }

        if ($day == null) {
            $start = 0;
            $end = 'inf';
        } else {
            $now = time();
            $start = ($now - ($day * 86400));
            $start = $start - ($start % 86400);
            $end = ($now + 86400) - (($now + 86400) % 86400);
        }

        $ret = array();
        $tmp =  $this->_redis->zRevRangeByScore($key, $end, $start, 
                                                array("withscores"=>true));
        foreach ($tmp as $id=>$weight) {
            $ret []= array("id" => $id, "weight"=>$weight);
        }

        return $ret;
    }
 
    public function setDeviceSeen($device_id, $news_ids) {
        $key = $this->getDeviceSentKey($device_id);
        
        call_user_func_array(array($this->_redis, "lPush"), 
                                   array_merge(array($key), $news_ids)
                            );
        $this->_redis->ltrim($key, 0, CACHE_SENT_MASK_MAX);
        $this->_redis->expire($key, CACHE_SENT_TTL);
    }
 
    public function getDeviceSeen($device_id) {
        $key = $this->getDeviceSentKey($device_id);
        $value = $this->_redis->lrange($key, 0, -1);
        if (!$value) {
            return array();
        }
        return $value;
    }

    private function getDeviceSentKey($device_id){
        return CACHE_SENT_QUEUE_PREFIX . $device_id;
    }

    public function setDeviceClick($device_id, $news_id, $timestamp) {
        $key = $this->getDeviceClickKey($device_id);
        $curArr = array("id" => $news_id, 
                        "timestamp" => $timestamp);
        $this->_redis->lPush($key, json_encode($curArr, true));
        $this->_redis->ltrim($key, 0, CACHE_CLICK_MASK_MAX);
        $this->_redis->expire($key, CACHE_CLICK_TTL);
    }

    public function getDeviceClick($device_id) {
        $key = $this->getDeviceClickKey($device_id);
        $clickNewsLst = $this->_redis->lrange($key, 0, -1);
        $resLst = array();
        foreach($clickNewsLst as $curNewsArr) {
            $resLst[] = json_decode($curNewsArr, true); 
        }
        return $resLst;
    }

    private function getDeviceClickKey($device_id){
        return CACHE_CLICK_QUEUE_PREFIX . $device_id;
    }

    public function getDeviceChannelCursor($device_id, $channel_id) {
        $key = $this->getDeviceChannelCursorKey($device_id, $channel_id);
        $value = $this->_redis->hGet(BACKUP_CHANNEL_CURSOR_KEY, $key);
        if (!$value) {
            return 0;
        }
        return intval($value);
    }

    public function setDeviceChannelCursor($device_id, $channel_id, $newValue) {
        $key = $this->getDeviceChannelCursorKey($device_id, $channel_id);
        if ($newValue >= 0) {
            $ret = $this->_redis->hSet(BACKUP_CHANNEL_CURSOR_KEY, $key, $newValue);
        }  
    }

    private function getDeviceChannelCursorKey($device_id, $channel_id) {
        return CHANNEL_USER_CURSOR_PREFIX . $channel_id . '_' . $device_id;
    }

    public function getDeviceBackupNews($device_id, $channel_id, $cnt) {
        $backup_idx = $this->getDeviceChannelCursor($device_id, $channel_id);
        $news_lst = $this->_redis->lrange(BACKUP_CHANNEL_LIST_PREFIX . $channel_id, 
                                          $backup_idx, $backup_idx + $cnt - 1);
        if (!$news_lst) {
            return array();
        }
        $new_backup_idx = $backup_idx + count($news_lst);
        $lst_total_cnt = $this->_redis->lSize(BACKUP_CHANNEL_LIST_PREFIX . $channel_id);
        if ($lst_total_cnt <= ($new_backup_idx + 1)) {
            $new_backup_idx = 0;
        }
        $this->setDeviceChannelCursor($device_id, $channel_id, $new_backup_idx);

        return $news_lst;
    }

    public function getChannelTopPopularNews($channelId) {
        $news_lst = $this->_redis->lRange('BA_POPULAR_NEWS_' . $channelId, 0, -1);
        if (!$news_lst) {
            return array();
        }

        $ret = array();
        foreach ($news_lst as $news) {
            $ret []= array("id" => $news, "weight" => 1);
        }
        return $ret;
    }
}
