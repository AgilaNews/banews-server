<?php
class Collect extends BaseModel {
    public $id;

    public $user_id;

    public $news_id;

    public $create_time;

    public static function getCollectId($user_id, $news_id) {
        $ret = Collect::findFirst(array (
                                    "columns" => "id",
                                     "conditions" => "user_id = ?1 AND news_id = ?2",
                                     "bind" => array(1=>$user_id, 2=>$news_id),
                                     ));

        return $ret ? $ret->id : 0;
    }
    
    public static function getAll($user_id, $last_id, $pn) {
        if (!$pn) {
            $pn = 20;
        }
        if ($pn >= 100) {
            $pn = 100;
        }
        
        if ($last_id) {
            $condition = "user_id = ?1 AND id > ?2";
            $bind = array(1 => $user_id, 2=>$last_id);
        } else {
            $condition = "user_id = ?1";
            $bind = array(1 => $user_id);
        }
        
        $collects = Collect::Find(array (
                                         "conditions" => $condition,
                                         "bind" => $bind,
                                         "limit" => $pn,
                                         "order" => "id",
                                         /*
                                           "cache" => array (
                                           "lifetime" => 1200,
                                           "key" => $this->config->cache->keys->collects,
                                           )*/
                                         )
                                  );
        return $collects;
    }

    
    public function getSource(){
        return "tb_collect";
    }
}
