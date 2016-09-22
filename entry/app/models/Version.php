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

    public static function getUpdateInfo($client_version, $os, $build) {
        $ret = Version::getAllUseable();
        if (!$ret) {
            return $ret;
        }
        
        $models = array();

        foreach ($ret as $model) {
            $models []= $model;
        }
        
        usort($models, function($a, $b) {
                return version_compare($a->client_version, $b->client_version);
            });

        if (count($models) == 0) {
            return null;
        }

        $cur_model = $min_model = $new_model = null;

        /*
           this section is relative tricky
           we have a sequence of version, some version is published for ios while others published for android
           some version number may skip serveral version 
            | for example, android develops v1.1.0 v1.1.1 v1.1.2, v1.1.1 is bug fix version, so v1.1.1 is not useful for ios
            | so ios do not have v1.1.1 version

           We defined four states by two bits to identify this situation
           0 not published for all
           1 only published for android
           2 only published for ios
           1 | 2 published for all platforms

           
           note: android has two main build package, main app is 1 called `Agila`, accessory is 2 called `Agila News`, fuck

           so we make sure that versions is sorted above, then just iterate from oldest version to newest version
           we can get three version numbers:
            1. min_version, minmal usable version, the first one we check is usable is certain platform
            2. new_version, last usable version, we keep track of usable version util nothing more saw
            3. cur_version, client version number set by client, some actions taken by server is based on client version
        */
        for ($i = 0; $i < count($models); $i++) {
            $model = $models[$i];
            if ($os == "ios") {
                if ($model->status & IOS_PUBLISHED) {
                    if (!($model->status & GRAY_RELEASE)) {
                        $new_model = $model;
                    }

                    if (!$min_model) {
                        $min_model = $model;
                    }
                    if ($model->client_version == $client_version) {
                        $cur_model = $model;
                    }
                }
            }

            if ($os == "android") {
                if ($model->status & ANDROID_PUBLISHED && $model->build_type == $build) {
                    if (!($model->status & GRAY_RELEASE)) {
                        $new_model = $model;
                    }

                    if (!$min_model) {
                        $min_model = $model;
                    }
                    if ($model->client_version == $client_version) {
                        $cur_model = $model;
                    }
                }
            }
        }

        $ret = array(
                "min_version" => "v" . $min_model->client_version,
                "new_version" => "v" . $new_model->client_version,
                     );

        if ($os == "ios") {
            $ret["update_url"] = $new_model->ios_update_url;
        } else {
            $ret["update_url"] = $new_model->update_url;
            $ret["avc"] = $new_model->android_version_code;
        }

        $ret["models"] = array(
                               "cur" => $cur_model,
                               "min" => $min_model,
                               "new" => $new_model,
                               );

        
        return $ret;
    }
}
