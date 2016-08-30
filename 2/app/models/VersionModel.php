<?php
/**
 * @file   ConfigModel.php
 * @author Gethin Zhang <zhangguanxing01@baidu.com>
 * @date   Wed Apr 13 14:43:13 2016
 * 
 * @brief  
 * 
 * 
 */

class VersionModel extends BaseModel {
    public $id;

    public $client_version;

    public $server_version;

    public $server_version;

    public $android_version_code;

    public $update_url;

    public $build_type;

    public $status;
    
    public function getSource(){
        return "tb_version";
    }

    private static version_model_comp($a, $b) {
        return version_compare($a->client_version, $b->client_version, ">");
    }

    public static function getAllAbove($base) {
        $ret = VersionModel::Find();

        if (!$ret) {
            return $ret;
        }

        $models = array();
        foreach ($ret as $model) {
            if (version_compare($model->client_version, $base, ">=")) {
                $models []= $model;
            }
        }

        usort($models, VersionModel::version_model_comp);

        return $models;
    }
}
