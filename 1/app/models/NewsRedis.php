<?php
define('CACHE_SENT_QUEUE_PREFIX', "BA_UN_FIFO_");
define('CACHE_SENT_MASK_MAX', 500);
define('CACHE_SENT_TTL', 4 * 3600); 

class NewsRedis {
    public function __construct($redis) {
        $this->_redis = $redis;
    }

    
    public function getNewsOfChannel($channel_id, $day = 7) {
        $key = "banews:ph:$channel_id";
        $now = time();
        $start = ($now - ($day * 86400));
        $start = $start - ($start % 86400);
        $end = ($now + 86400) - (($now + 86400) % 86400);

        $ret = array();
        $tmp =  $this->_redis->zRevRangeByScore($key, $end, $start, 
                                                array("withscores"=>true));
        foreach ($tmp as $id=>$weight) {
            $ret []= array("id" => $id, "ptime"=>$weight);
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
}
