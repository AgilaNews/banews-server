<?php
/**
 * @file   Interests.php
 * @authors Zhao Yulong (elysium.zyl@gmail.com)
 * @date    2016-12-20 14:00:03
 * 
 * @brief  
 * 
 */
use Phalcon\DI;

/*

CREATE TABLE `tb_user_interests` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'auto increment id',
    `user_id` varchar(64) DEFAULT NULL COMMENT 'user sign',
    `interest_id` bigint(20) NOT NULL COMMENT 'interest id',
    `interest_name` varchar(256) NOT NULL COMMENT 'interest name',
    `upload_time` int(11) NOT NULL COMMENT 'timestamp this data uploaded',
    `device_id` varchar(64) NOT NULL COMMENT 'device id',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

*/

class UserInterests extends BaseModel {
    public $id;

    public $user_id;

    public $interest_id;

    public $interest_name;

    public $upload_time;

    public $device_id;

    public function getSource(){
        return "tb_user_interests";
    }
}
