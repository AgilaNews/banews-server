<?php

use Phalcon\DI;

class NewsSnsWidget extends BaseModel {
    public $id;

    public $news_id;

    public $news_url_sign;

    public $news_pos_id;

    public $sns_type;

    public $widget_id;

    public $screen_name;

    public $icon_url;

    public $icon_origin_url;

    public $content;

    public $image_url;

    public $image_origin_url;

    public $image_url_sign;

    public $image_meta;

    public $update_time;

    public $is_deadlink;

    public function getSource(){
        return "tb_news_sns_widget";
    }

    public static function getSnsWidgetOfNews($news_sign) {
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            $value = $cache->get(CACHE_SNS_WIDGET_PREFIX . $news_sign);
            if ($value) {
                $rs = unserialize($value);
                return $rs;
            }
        }

        $key = CACHE_SNS_WIDGET_PREFIX. $news_sign;
        $crit = array(
                      "conditions" => "news_url_sign=?1",
                      "bind" => array(1 => $news_sign),
                      );
        
        $rs = NewsSnsWidget::find($crit);
        if ($cache) {
            $cache->multi();
            $cache->set($key, serialize($rs));
            $cache->expire($key, CACHE_SNS_WIDGET_TTL);
            $cache->exec();
        }

        return $rs;
    }
    
}
