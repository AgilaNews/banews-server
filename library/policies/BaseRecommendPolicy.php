<?php
abstract class BaseRecommendPolicy {
    public function __construct($di) {
        $this->_di = $di;
        $redis = $this->_di->get('cache');
        if (!$redis) {
            throw new HttpException(ERR_INTERNAL_DB, "redis not found");
        }

        $this->redis = new NewsRedis($redis);
        $this->logger = $this->_di->get('logger');
    }

    abstract public function sampling($channel_id, $device_id, $user_id, 
        $myself, $pn = 3, $day_till_now=7, array $options = null);

    protected function logPolicy($msg) {
        if ($this->logger) {
            $this->logger->info($msg);
        }
    }
}
