<?php
/**
 * @file   Version.php
 * @author Gethin Zhang <zhangguanxing01@baidu.com>
 * @date   Wed Apr 13 14:43:13 2016
 * 
 * @brief  
 * 
 * 
 */
use Phalcon\DI;

class Version extends BaseModel {
    public $id;

    public $client_version;

    public $server_version;

    public $android_version_code;

    public $update_url;

    public $build_type;

    public $status;

    public function getSource(){
        return "tb_version";
    }

    public static function getAllUseable() {
        $cache = DI::getDefault()->get('cache');

        if ($cache) {
            $value = $cache->get(CACHE_VERSION_PREFIX);
            if ($value) {
                return unserialize($value);
            }
       }

       $versions = Version::Find(array(
                       "conditions" => "status <> ?1",
                       "bind" => array(1 => NOT_PUBLISHED),
                       ));

       if ($versions && $cache)  {
           $cache->multi();
           $cache->set(CACHE_VERSION_PREFIX, serialize($versions));
           $cache->expire(CACHE_VERSION_PREFIX, CACHE_VERSION_TTL);
           $cache->exec();
       }

       return $versions;
    }
}
