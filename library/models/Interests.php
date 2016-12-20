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

CREATE TABLE `tb_interests` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'auto increment id',
    `name` varchar(32) NOT NULL,
    `is_valid` tinyint(4) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

insert into `tb_interests` (`name`, `is_valid`) values
('World', 1),
('Sports', 1),
('Entertainment', 1),
('Games', 1),
('Lifestyle', 1),
('Business', 1),
('Sci&Tech', 1),
('Opinion', 1),
('National', 1),
('Photos', 1),
('GIFs', 1),
('NBA', 1),
('Food', 1),
('Videos', 1);
*/

class Interests extends BaseModel {
    public $id;

    public $name;

    public $is_valid;

    public function getSource(){
        return "tb_interests";
    }

    public static function getAll() {
        return Interests::find(array("conditions" => "is_valid=1"));
    }
}
