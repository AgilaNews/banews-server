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
class Version extends BaseModel {
    public $id;

    public $client_version;

    public $server_version;
    
    public function getSource(){
        return "tb_version";
    }
}
