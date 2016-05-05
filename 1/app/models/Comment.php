<?php
class Comment extends BaseModel {
    public $id;

    public $user_id;

    public $news_id;

    public $user_comment;

    public $create_time;

    public static function getAll($news_id, $last_id, $pn) {
        if (!$pn) {
            $pn = 20;
        }
        if ($pn >= 100) {
            $pn = 100;
        }

        if ($last_id) {
            $condition = "news_id = ?1 AND id > ?2";
            $bind = array(1 => $news_id, 2=>$last_id);
        } else {
            $condition = "news_id = ?1";
            $bind = array(1 => $news_id);
        }
        
        $comments = Comment::Find(array (
                                         "conditions" => $condition,
                                         "bind" => $bind,
                                         "limit" => $pn,
                                         "order" => "create_time DESC",
                                         /*
                                           "cache" => array (
                                           "lifetime" => 1200,
                                           "key" => $this->config->cache->keys->comments,
                                           )*/
                                         )
                                  );
        return $comments;
    }

    public static function getCount($news_id) {
        $ret = Comment::count(
            array(
                "conditions" => "news_id=?1",
                "bind" => array(1=>$news_id),
            )
        );

        return $ret;
    }

    public function getSource(){
        return "tb_comment";
    }
}
