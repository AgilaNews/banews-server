<?php
use Phalcon\DI;

class Collect extends BaseModel {
    public $id;

    public $user_sign;

    public $news_sign;

    public $create_time;

    protected static function getKey($user_sign, $news_sign) {
        return CACHE_COLLECT_PREFIX . $user_sign . "_" . $news_sign;
    }
    
    protected static function _saveCollectIdCache($user_sign, $news_sign, $id) {
        $cache = DI::getDefault()->get('cache');
        $key = self::getKey($user_sign, $news_sign);
        if ($cache) {
            $cache->multi();
            $cache->set($key, $id);
            $cache->expire($key, CACHE_COLLECT_TTL);
            $cache->exec();
        }
    }
    
    public static function getCollectId($user_sign, $news_sign) {
        $cache = DI::getDefault()->get('cache');
        $key = self::getKey($user_sign, $news_sign);
        
        if ($cache) {
            $value = $cache->get($key);
            if ($value) {
                return $value;
            }
        }
        
        $ret = Collect::findFirst(array (
                                         "columns" => "id",
                                         "conditions" => "user_sign = ?1 AND news_sign = ?2",
                                         "bind" => array(1=>$user_sign, 2=>$news_sign),
                                         ));

        $ret =  $ret ? $ret->id : 0;
        self::_saveCollectIdCache($user_sign, $news_sign, $ret);
        
        return $ret;
    }

    public function save($data = null, $whitelist = null) {
        $ret = parent::save();
        if ($ret) {
            self::_saveCollectIdCache($this->user_sign, $this->news_sign, $this->id);
        }
        
        return $ret;
    }

    public static function batchDelete(array $ids) {
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            $cache->multi();
        }
        
        foreach ($ids as $id) {
            $model = self::findFirst(array("conditions" => "id=?1",
                                           "bind"=> array(1=>$id)
                                           ));
            if ($model) {
                $model->delete();
            }
        }
        
        if ($cache) {
            $cache->exec();
        }
    }
    
    public function delete() {
        $ret = parent::delete();
        $cache = $this->getDI()->get('cache');
        if ($ret) {
            $key = self::getKey($this->user_sign, $this->news_sign);
            $cache->delete($key);
        } else {
            $this->getDI()->get('logger')->warning("delete collect model error : " . $this->getMessages());
        }
    }

    public static function getAll($user_sign, $last_id, $pn) {
        if (!$pn) {
            $pn = 5;
        }
        if ($pn >= 100) {
            $pn = 100;
        }
        
        if ($last_id) {
            $condition = "user_sign = ?1 AND id < ?2";
            $bind = array(1 => $user_sign, 2=>$last_id);
        } else {
            $condition = "user_sign = ?1";
            $bind = array(1 => $user_sign);
        }
        
        $collects = Collect::Find(array (
                                         "conditions" => $condition,
                                         "bind" => $bind,
                                         "limit" => $pn,
                                         "order" => "id DESC",
                                         )
                                  );
        return $collects;
    }

    
    public function getSource(){
        return "tb_collect";
    }
}
