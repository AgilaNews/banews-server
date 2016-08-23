<?php
/**
 * @file   ChannelDispatch.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Mon Aug 22 18:09:54 2016
 * 
 * @brief  
 * 
 * 
 */
class ChannelDispatch extends BaseModel {
    public $id;

    public $version;

    public $content;

    public $create_time;

    public $update_time;

    public function getSource(){
        return "tb_channel_dispatch";
    }

    public function getNewestVersion(){
        return ChannelDispatch::maximum(
            array(
                "column" => "version",
            )
        );
    }
}
