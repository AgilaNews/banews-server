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
}