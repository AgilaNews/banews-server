<?php
/**
 * @file   Package.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Sun Sep 18 11:03:28 2016
 * 
 * @brief  
 * 
 * 
 */
use Phalcon\DI;

class Package extends BaseModel {
    public $id;

    public $md5;

    public $version;

    public $lastest_publish_version;

    public $status;

    public $download_url;

    public $update_time;
    
    public function getSource() {
        return "tb_package";
    }
    
    public static function getAllUseable() {
        $cache = DI::getDefault()->get('cache');

        if ($cache) {
            $value = $cache->get(CACHE_CHANNELS_KEY);
            if ($value) {
                $packages = unserialize($value);
            }
        }

        if (!$packages) {
            $packages =
                Package::find(array(
                                    "conditions" => "status <> ?1",
                                    "bind" => array(1 => NOT_PUBLISHED),
                                    ));
            if ($packages && $cache) {
                $cache->multi();
                $cache->set(CACHE_PACKAGE_PREFIX, serialize($packages));
                $cache->expire(CACHE_PACKAGE_PREFIX, CACHE_PACKAGE_TTL);
                $cache->exec();
            }
        }

        return $packages;
    }

    
    public static function getNewestVersion(){
        $newest = 0;
        $ret = null;
        $packages = Package::getAllUseable();
        if (!$packages) {
            return null;
        }

        foreach ($packages as $package) {
            if ($package->version > $newest) {
                $newest = $package->version;
                $ret = $package;
            }
        }

        return $ret;
    }

}
