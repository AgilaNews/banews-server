<?php
/**
 * @file   BaseModel.php
 * @author Gethin Zhang <zhangguanxing01@baidu.com>
 * @date   Wed Apr 13 11:17:58 2016
 * 
 * @brief  
 * 
 * 
 */
use Phalcon\Mvc\Model;
use Phalcon\DI;

class BaseModel extends Model {
    public function initialize() {
        $this->setReadConnectionService('db_r');
        $this->setWriteConnectionService('db_w');
    }
}
