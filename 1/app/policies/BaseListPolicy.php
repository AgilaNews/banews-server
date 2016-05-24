<?php
abstract class BaseListPolicy {
    public function __construct($di) {
        $this->_di = $di;
        $redis = $this->_di->get('cache');
        if (!$redis) {
            throw new HttpException(ERR_INTERNAL_DB, "redis not found");
        }

        $this->redis = new NewsRedis($redis);
        $this->logger = $this->_di->get('logger');
    }

    abstract public function sampling($channel_id, $device_id, $user_id, $pn = 10, $prefer='later', array $options = null);

    public function setDeviceSent($device_id, $news_ids) {
        $this->redis->setDeviceSeen($device_id, $news_ids); 
    }

    protected function logPolicy($msg) {
        if ($this->logger) {
            $this->logger->info($msg);
        }
    }

    protected function getAllUnsent($channel_id, $device_id) {
        $sent = $this->redis->getDeviceSeen($device_id);
        $ready_news_list = $this->redis->getNewsOfchannel($channel_id);
        $valid_news_list = array();

        foreach ($ready_news_list as $ready_news=>$weight) {
            if (!in_array($ready_news, $sent)) {
                $valid_news_list []= array("id" => $ready_news,
                                         "ptime" => $weight);
            }
        }

        return $valid_news_list;
    }
}
