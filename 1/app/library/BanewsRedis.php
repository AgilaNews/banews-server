<?php
use Phalcon\Cache\Backend\Redis as BackRedis;

class BanewsRedis extends BackRedis {
    public function __construct($front, $options = array()) {
        parent::__construct($front, $options);
    }

    public function __call($name, $arguments) {
        if (method_exists($this, $name)) {
            return $this->$name($arguments);
        }

        if (!$this->_redis) {
            $this->_connect();
        } 

        if (method_exists($this->_redis, $name)) {
            return
            call_user_func_array(array($this->_redis, $name),
                                $arguments);
        }
    }
}
