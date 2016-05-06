<?php
use Phalcon\Cache\Backend\Redis as BackRedis;

class BanewsRedis extends BackRedis {
    public function __construct($front, $options = array()) {
        parent::__construct($front, $options);
    }
}
