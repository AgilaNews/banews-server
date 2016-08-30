<?php
use Phalcon\DI;

class Channel extends BaseModel {
    public $channel_id;

    public $parent_id;

    public $name;

    public $publish_latest_version;

    public $priority;

    public $fixed;

    public $is_visible;

    public function getSource() {
        return "tb_channel";
    }

    public static function getAllVisible($client_version){
        $cache = DI::getDefault()->get('cache');

        if ($cache) {
            $value = $cache->get(CACHE_CHANNELS_KEY);
            if ($value) {
                $channels = unserialize($value);
            }
        } 

        if (!$channels) {
            $channels = 
                Channel::find(array(
                                    "conditions" => "is_visible = 1",
                                    "order" => "priority"
                                    ));
            
            if ($cache && $channels) {
                $cache->multi();
                $cache->set(CACHE_CHANNELS_KEY, serialize($channels));
                $cache->expire(CACHE_CHANNELS_KEY, CACHE_CHANNELS_TTL);
                $cache->exec();
            }
        }
            
        $ret = array();
        foreach ($channels as $channel) {
            if (version_compare($client_version, $channel->publish_latest_version, ">=")) {
                $ret []= $channel;    
            }
        }

        return $ret;
    } 
}
