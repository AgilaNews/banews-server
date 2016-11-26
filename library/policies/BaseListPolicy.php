<?php
abstract class BaseListPolicy {
    public function __construct($di) {
        $this->_di = $di;
        $redis = $this->_di->get('cache');
        $this->_db = $this->_di->get('db_r');
        $this->_logger = $this->_di->get('logger');
        if (!$redis || !$this->_db || !$this->_logger) {
            throw new HttpException(ERR_INTERNAL_DB, "get services error");
        }

        $this->_cache = new NewsRedis($redis);
    }

    abstract public function sampling($channel_id, $device_id, $user_id, $pn, $day_till_now, $prefer, 
                                      array $options = array());

    public function setDeviceSent($device_id, $news_ids, $max = CACHE_SENT_MASK_MAX, $ttl = CACHE_SENT_TTL) {
        $this->_cache->setDeviceSeen($device_id, $news_ids, $max, $ttl); 
    }

    protected function logPolicy($msg) {
        if ($this->_logger) {
            $this->_logger->info($msg);
        }
    }

    protected function getAllUnsentNewsByBloomfilter($filterName, $channel_id, $device_id, $day_till_now) {
        $ready_news_list = $this->_cache->getNewsOfChannel($channel_id, $day_till_now);

        $bf_service = $this->_di->get("bloomfilter");
        $ret = $bf_service->filter(
                                   $filterName,
                                   $ready_news_list,
                                   function($news) use ($device_id) {
                                       return $device_id . "_" . $news["id"];
                                   }
                                   );
        
        
        return $ret;
    }

    protected function getAllUnsent($channel_id, $device_id, $day_till_now) {
        if($channel_id == 30001) {
            return $this->getAllUnsentNewsByBloomfilter(BloomFilterService::FILTER_FOR_VIDEO, $channel_id, $device_id, $day_till_now);
        }
        if($channel_id == 10011) {
            return $this->getAllUnsentNewsByBloomfilter(BloomFilterService::FILTER_FOR_IMAGE, $channel_id, $device_id, $day_till_now);
        }
        if($channel_id == 10012) {
            return $this->getAllUnsentNewsByBloomfilter(BloomFilterService::FILTER_FOR_GIF, $channel_id, $device_id, $day_till_now);
        }
        
        $sent = $this->_cache->getDeviceSeen($device_id);
        $ready_news_list = $this->_cache->getNewsOfChannel($channel_id, $day_till_now);
        $valid_news_list = array();

        foreach ($ready_news_list as $ready_news) {
            if (!in_array($ready_news["id"], $sent)) {
                $valid_news_list []= $ready_news;
            }
        }

        return $valid_news_list;
    }
}
