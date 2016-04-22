<?php

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Validator\Uniqueness;

class User extends Model {
    const SOURCE_FB = 1;
    const SOURCE_GP = 2;
    const SOURCE_TW = 3;

    const SOURCE_MAP = array(
        "facebook" => self::SOURCE_FB,
        "goolgeplus" => self::SOURCE_GP,
        "twitter" => self::SOURCE_TW,
    );

    public $id;

    public $uid;

    public $source;

    public $name;

    public $gender;

    public $portrait_srcurl;

    public $portrait_url;

    public $email;

    public $create_time;

    public $update_time;

    public function validation(){
        $this->validate(
            new Uniqueness(
                array(
                    "field" => array("uid", "source"),
                    "message" => "uid and source dup",
                )
            )
        ); 

        return $this->validationHasFailed() != true;
    }

    public function getSource() {
        return "tb_user";
    }
}
