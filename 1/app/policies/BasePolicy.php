<?php
class BasePolicy {
    public function __construct($di) {
        $this->_di = $di;
        $redis = $this->_di->get('cache');
        if (!$redis) {
            throw new HttpException(ERR_INTERNAL_DB, "redis not found");
        }

        $this->redis = new NewsRedis($redis);
        $this->logger = $this->_di->get('logger');
    }

    protected function logPolicy($msg) {
        if ($this->logger) {
            $this->logger->info($msg);
        }
    }
}
