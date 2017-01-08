<?php

abstract class BaseNewsRanker {

    public function __construct($di) {
        $this->di = $di;
        $redis = $this->di->get('cache');
        $this->db = $this->di->get('db_r');
        $this->logger = $this->di->get('logger');
        if (!$redis || !$this->db || !$this->logger) {
            throw new HttpException(ERR_INTERNAL_DB, "get services error");
        }
        $this->cache = new NewsRedis($redis);
    }

    abstract public function getRankerTag();

    abstract public function ranking($channelId, $deviceId, $newsObjLst, 
        $prefer, $newsCnt, array $options=array());
}
