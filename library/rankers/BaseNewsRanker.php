<?php

abstract class BaseNewsRanker {

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

    abstract public function getRankerTag();

    abstract public function ranking($channelId, $deviceId, $newsObjLst, 
        $prefer, $newsCnt, array $options=array());
}
