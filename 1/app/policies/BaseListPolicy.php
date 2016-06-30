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

    abstract public function sampling($channel_id, $device_id, $user_id, $pn = 10, $prefer='later', array $options = null);

    public function setDeviceSent($device_id, $news_ids) {
        $this->_cache->setDeviceSeen($device_id, $news_ids); 
    }

    protected function logPolicy($msg) {
        if ($this->_logger) {
            $this->_logger->info($msg);
        }
    }

    protected function getAllUnsent($channel_id, $device_id) {
        $sent = $this->_cache->getDeviceSeen($device_id);
        $ready_news_list = $this->_cache->getNewsOfchannel($channel_id);
        $valid_news_list = array();

        foreach ($ready_news_list as $ready_news) {
            if (!in_array($ready_news["id"], $sent)) {
                $valid_news_list []= $ready_news;
            }
        }

        return $valid_news_list;
    }
}
