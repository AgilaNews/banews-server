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

    protected function tryBloomfilter($channel_id, $device_id, $news_list) {
        switch($channel_id){
        case "30001":
            $filterName = BloomFilterService::FILTER_FOR_VIDEO;
            break;
        case "10011":
            $filterName = BloomFilterService::FILTER_FOR_IMAGE;
            break;
        case "10012":
            $filterName = BloomFilterService::FILTER_FOR_GIF;
            break;
        default:
            return null;
        }
        
        $bf_service = $this->_di->get("bloomfilter");
        $ret = $bf_service->filter(
                                   $filterName,
                                   $news_list,
                                   function($news) use ($device_id) {
                                       return $device_id . "_" . $news["id"];
                                   }
                                   );
        
        
        return $ret;
    }

    protected function getReadyNews($channel_id, $day_till_now) {
        return $this->_cache->getNewsOfChannel($channel_id, $day_till_now);
    }
    
    protected function getAllUnsent($channel_id, $device_id, $day_till_now) {
        $ready_news_list = $this->getReadyNews($channel_id, $day_till_now);
        $ret = $this->tryBloomfilter($channel_id, $device_id, $ready_news_list);
        
        if ($ret !== null) {
            return $ret;
        }
        
        $sent = $this->_cache->getDeviceSeen($device_id);
        $valid_news_list = array();

        foreach ($ready_news_list as $ready_news) {
            if (!in_array($ready_news["id"], $sent)) {
                $valid_news_list []= $ready_news;
            }
        }

        return $valid_news_list;
    }
}
