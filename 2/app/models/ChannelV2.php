/**
 * @file   ChannelV2.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Mon Aug 22 17:21:53 2016
 * 
 * @brief  
 * 
 * 
 */
class ChannelV2 extends BaseModel {
    public $channel_id;

    public $name;

    public $publish_latest_version;

    public $is_visible;

    public function getSource(){
        return "tb_channel_v2";
    }

    public static function getChannelsOfVersion(int $channel_version, $client_version) {
        $cache = DI::getDefault()->get('cache');

        if ($cache) {
            $value = $cache->get(sprintf(CACHE_CHANNELS_V2_KEY, $channel_version));
            return json_decode($value, true);
        }

        $ret = ChannelDispatch::findFirst(array(
                                          "version" => "version=?1",
                                          "bind" => array($channel_version),
                                          ));

        /*content formatting
          [
          {
          "channel_id": 10001,
          "tag": "1", // 1 is `hot` 0 is `default`
          "fixed": "1|0",
          }
          ]
        */
        $channel_list = ChannelV2::getAll($client_version);
        $channel_map = array();
        foreach ($channel_list as $channel) {
            $channel_map[$channel->id] = $channel;
        }
        
        $dispatch_list = json_decode($ret["content"]);
        $ret = array();

        foreach ($dispatch_list as $cell) {
            if (!in_array($cell["channel_id"], $channel_map)) {
                continue;
            }
            $channel_detail = $channel_map[$cell["channel_id"]];
            
            $ret = array_merge($cell, array(
                                            "name" => $channel_detail->name,
                                            ));
        }

        return $ret;
    }

    public static function getAll($client_version) {
        $channels = ChannelV2::find(array("conditions" => "is_visible=1"));
        $ret = [];

        foreach ($channels as $channel) {
            $ret []= $channel;
            if (version_compare($client_version, $channel->publish_latest_version, ">=")) {
                $ret []= $channel;    
            }
        }

        return $ret;
    }

    
}
