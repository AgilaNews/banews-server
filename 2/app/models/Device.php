<?php
use Phalcon\DI;
class Device extends BaseModel {
    public $id;

    public $token;

    public $os;

    public $os_version;

    public $vendor;

    public $imsi;

    public $user_id;

    public $device_id;

    public function getSource() {
        return "tb_device";
    }

    public static function getByUserId($uid){
        $device = $Device::findFirst(array(
                               "conditions" => "user_id = ?1"
                               "bind" => array("user_id" => $uid ),
                               ));
        return $device;
    }

    public static function getByDeviceId($device_id){
        $device = $Device::findFirst(array(
                               "conditions" => "device_id = ?1"
                               "bind" => array("device_id" => $device_id ),
                               ));
        return $device;
    }
}