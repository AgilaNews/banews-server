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
            $value = $cache->get(CHANNELS_CACHE_KEY);
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
            $cache->set(CHANNELS_CACHE_KEY, serialize($channels));
            $cache->expire(CHANNELS_CACHE_KEY, CHANNELS_CACHE_TTL);
            $cache->exec();
        }

        return $channels;
    } 
}
