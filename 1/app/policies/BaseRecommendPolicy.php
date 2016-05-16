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

    abstract public function sampling($channel_id, $device_id, $user_id, $pn = 3, array $options = null);

    protected function logPolicy($msg) {
        if ($this->logger) {
            $this->logger->info($msg);
        }
    }
}
