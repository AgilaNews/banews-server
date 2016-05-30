<?php

use Phalcon\DI;

class News extends BaseModel {
    public $id;

    public $update_time;

    public $url_sign;

    public $source_url;

    public $channel_id;

    public $title;

    public $source_name;

    public $public_time;

    public $fetch_time;

    public $summary;

    public $json_text;

    public $ext_json_text;

    public $tag;

    public $content_sign;

    public $related_sign;

    public $display_type;

    public $shared_url;

    public $content_type;

    public $liked;

    public $is_visible;

    public function initialize(){
        $this->skipAttributes(
            array ("summary",
                    "ext_json_text",
                    "tag",
                    "related_sign",)
        );
    }
    public function getSource(){
        return "tb_news";
    }

    protected static function _getFromDB($sign) {
        $crit = array ("conditions" => "url_sign = ?1",
                       "bind" => array (1 => $sign),
                      );

        $news_model = News::findFirst($crit);
        return $news_model;
    }

    protected static function _getFromCache($sign) {
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            $key = CACHE_NEWS_PREFIX . $sign;
            $value = $cache->get($key);
            if ($value) {
                $model = new News();
                $model->unserialize($value);
                return $model;
            }
        }

        return null;
    }

    protected static function _saveToCache($model){
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            $key = CACHE_NEWS_PREFIX . $model->url_sign;
            $cache->multi();
            $cache->set($key, $model->serialize());
            $cache->expire($key, CACHE_NEWS_TTL);
            $cache->exec();
        }
    }

    protected static function _batchSaveToCache(array $models) {
        $cache = DI::getDefault()->get('cache');

        $cache->multi();

        foreach ($models as $sign => $model) {
            $cache->set(CACHE_NEWS_PREFIX . $model->url_sign, $model->serialize());
            $cache->expire(CACHE_NEWS_PREFIX . $model->url_sign, CACHE_NEWS_TTL);
        }

        $cache->exec();
        return;
    }

    protected static function _batchGetFromCache($signs) {
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            $keys = array();
            foreach ($signs as $sign) {
                $keys []= CACHE_NEWS_PREFIX . $sign;
            }
            $rret = $cache->mget($keys);
            if (!$rret) {
                return array();
            }

            $ret = array();
            foreach ($rret as $idx => $value) {
                if ($value) {
                    $model = new News();
                    $model->unserialize($value);
                    $ret[$signs[$idx]] = $model;
                } else {
                    $ret[$signs[$idx]]= null;
                }
            }

            return $ret;
        }

        return array();
    }


    protected static function _batchGetFromDB($signs) {
        if (count($signs) == 0) {
            return array();
        }

        $crit = array("conditions" => "url_sign IN ({signs:array})",
                      "bind" => array("signs" => $signs));

        $ret = News::find($crit);
        foreach ($ret as $model) {
            $models[$model->url_sign] = $model;
        }
        self::_batchSaveToCache($models);
        return $models;
    }


    public static function batchGet(array $signs) {
        $ret = self::_batchGetFromCache($signs);
        if (!$ret) {
            return self::_batchGetFromDB($signs);
        }

        $left = array();
        foreach ($ret as $sign => $model) {
            if (!$model) {
                $left []= $sign;
            }
        }

        if (count($left) != 0) {
            $left_models = self::_batchGetFromDB($left);
            $ret = array_merge($ret, $left_models);
        }

        return $ret;
    }
    
    public static function getBySign($sign) {
        $model = self::_getFromCache($sign);
        if ($model) {
            return $model;
        } else {
            $model =  self::_getFromDB($sign);
            if ($model) {
                self::_saveToCache($model);
            }
            return $model;
        }
    }

    public function save($data = null, $whitelist = null) {
        $ret = parent::save($data, $whitelist);
        if ($ret) {
            News::_saveToCache($this);
        }
        return $ret;

    } 
}
