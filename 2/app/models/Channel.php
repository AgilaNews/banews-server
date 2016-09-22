<?php
use Phalcon\DI;

class Channel extends BaseModel {
    public $channel_id;

    public $parent_id;

    public $name;

    public $priority;

    public $publish_lastest_version;

    public $fixed;

    public $is_visible;

    public function getSource() {
        return "tb_channel";
    }

    public static function getAllVisible(){
        $cache = DI::getDefault()->get('cache');

        if ($cache) {
            $value = $cache->get(CACHE_CHANNELS_KEY);
            if ($value) {
                return unserialize($value);
            }
        }

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

        return $channels;
    } 
}
