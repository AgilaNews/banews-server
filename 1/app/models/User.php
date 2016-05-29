<?php

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Validator\Uniqueness;
use Phalcon\DI;

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

    
    public function getSource() {
        return "tb_user";
    }

    
    public static function getBySign($sign) {
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            $value = $cache->get(CACHE_USER_PREFIX . $sign);
            if ($value) {
                $user_model = new User();
                $user_model->unserialize($value);
                return $user_model;
            }
        }
        
        $user_model = User::findFirst(array ("conditions" => "sign = ?1",
                                             "bind" => array (1 => $sign),
                                             ));

        if ($user_model && $cache) {
            $cache->multi();
            $cache->set(CACHE_USER_PREFIX . $sign, $user_model->serialize());
            $cache->expire(CACHE_USER_TTL);
            $cache->exec();
        }
        
        return $user_model;
    }
    
    public static function getBySourceAndId($source, $uid){
        $user = User::findFirst( 
            array(
            "conditions" => "source = ?1 and uid = ?2",
            "bind" => array(1 => $source, 
                            2 => $uid,
			    ),
            )
        );
        return $user;
    }
}
