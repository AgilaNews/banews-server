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
    
    public function getSource(){
        return "tb_version";
    }
        
    public static function getByClientVersion($client_version) {
        $cache = DI::getDefault()->get('cache');

        if ($cache) {
            $value = $cache->get(CACHE_VERSION_PREFIX . $client_version);
            if ($value) {
                $model = new Version();
                $model->unserialize($value);
                return $model;
            }
        }

        $version = Version::findFirst(array(
            "conditions" => "client_version = ?1",
            "bind" => array(1 => $client_version),
        ));

        if ($version) {
            $key = CACHE_VERSION_PREFIX . $client_version;
            $cache->multi();
            $cache->set($key, $version->serialize());
            $cache->expire($key, CACHE_VERSION_TTL);
            $cache->exec();
        }

        return $version;
    }
}
