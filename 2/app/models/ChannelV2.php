<?php
/**
 * @file   ChannelV2.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Mon Aug 22 17:21:53 2016
 * 
 * @brief  
 * 
 * 
 */
use Phalcon\DI;

class ChannelV2 extends BaseModel {
    public $channel_id;

    public $name;

    public $publish_latest_version;

    public $ios_publish_latest_version;

    public $is_visible;

    public function getSource(){
        return "tb_channel_v2";
    }

    public static function getChannelsOfVersion($channel_version, $client_version, $os) {
        $cache = DI::getDefault()->get('cache');

        if ($cache) {
            $value = $cache->get(sprintf(CACHE_CHANNELS_V2_KEY, $channel_version));
            if ($value) {
                return json_decode($value, true);
            }
        }


        $cdm = ChannelDispatch::findFirst(array(
                                          "conditions" => "version=?1",
                                          "bind" => array(1=>$channel_version),
                                          ));

        /*content formatting
          [
          {
          "id": 10001,
          "tag": "1", // 1 is `hot` 0 is `default`
          "fixed": "1|0",
          }
          ]
        */
        $channel_list = ChannelV2::getAll($client_version, $os);

        $channel_map = array();
        foreach ($channel_list as $channel) {
            $channel_map[$channel->channel_id] = $channel;
        }

        $dispatch_list = json_decode($cdm->content, true);

        $ret = array();
        
        foreach ($dispatch_list as $cell) {
            if (!array_key_exists($cell["id"], $channel_map)) {
                continue;
            }
            $channel_detail = $channel_map[$cell["id"]];
            
            $ret []= array_merge($cell, array(
                                            "name" => $channel_detail->name,
                                            "index" => count($ret),
                                            ));
        }

        if ($cache) {
            //TODO save to cache
        }

        return $ret;
    }

    public static function getAll($client_version, $os) {
        $channels = ChannelV2::find(array("conditions" => "is_visible=1"));
        $ret = [];

        foreach ($channels as $channel) {
            if ($os == "ios" && version_compare($client_version, $channel->ios_publish_latest_version, ">=")) {
                $ret []= $channel;    
            } else {
                if (version_compare($client_version, $channel->publish_latest_version, ">=")) {
                    $ret []= $channel;    
                }
            }
        }

        return $ret;
    }

    
}
