<?php

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Validator\Uniqueness;

class User extends BaseModel {
    const SOURCE_FB = 1;
    const SOURCE_GP = 2;
    const SOURCE_TW = 3;

    const SOURCE_MAP = array(
        "facebook" => self::SOURCE_FB,
        "googleplus" => self::SOURCE_GP,
        "twitter" => self::SOURCE_TW,
    );
    const SOURCE_UNMAP = array(
        self::SOURCE_FB => "facebook",
        self::SOURCE_GP => "googleplus",
        self::SOURCE_TW => "twitter",
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

    protected static function getCacheKey($param) {
      return CACHE_USER_PREFIX . $param;
    }

    
    public static function getBySign($sign) {
        $user_model = User::findFirst(array ("conditions" => "sign = ?1",
                                             "bind" => array (1 => $sign),
                                             
                                             "cache" => array (
                                                              "lifetime" => CACHE_USER_TTL,
							                                  "key" => self::getCacheKey("sign_" . $sign),
                                                               ),
                                             ));
        return $user_model;
    }

    
    public static function getById($id) {
        $user_model = User::findFirst(array ("conditions" => "id = ?1",
                                             "bind" => array (1 => $id),
                                             "cache" => array (
								                 "lifetime" => CACHE_USER_TTL,
								                 "key" => self::getCacheKey("id_" . $sign),
                                             )));
        
        return $user_model;
    }

	  
    public static function getBySourceAndId($source, $uid){
        $user = User::findFirst( 
            array(
            "conditions" => "source = ?1 and uid = ?2",
            "bind" => array(1 => $source, 
                            2 => $uid,
			    ),

        "cache" => array(
                 "lifetime" => CACHE_USER_TTL,
                 "key" => self::getCacheKey("is_" .  $source . "_" . $uid . ""),
                 ),
            )
        );
        return $user;
    }

    public function expireSourceAndIdCache($source, $uid) {
        $this->deleteCache(self::getCacheKey("is_" .  $source . "_" . $uid . ""));
    }
}
