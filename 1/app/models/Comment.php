<?php
class Comment extends BaseModel {
    public $id;

    public $user_id;

    public $news_id;

    public $user_comment;

    public $create_time;

    public static function getCacheKeys($news_id) {
        return CACHE_COMMENTS_PREFIX . $news_id;
    }

    public static function getAll($news_id, $last_id, $pn) {
        $crit = array (
            "limit" => 20,
            "order" => "create_time DESC",
        );
        
        if (!$pn && !$last_id) {
            $crit["cache"] = array (
            "lifetime" => CACHE_COMMENTS_TTL,
            "key" => self::getCacheKeys($news_id),
            );
        }

        if ($pn) {
            $crit["limit"] = $pn = $pn >= 100 ? 100 : $pn;
        }

        if ($last_id) {
            $crit["conditions"] = "news_id = ?1 AND id > ?2";
            $crit["bind"] = array(1 => $news_id, 2=>$last_id);
        } else {
            // if it is pagnation request, we won't cache
            $crit["conditions"] = "news_id = ?1";
            $crit["bind"] = array(1 => $news_id);
        }

        
        $comments = Comment::Find($crit);
        return $comments;
    }

    public static function getCount($news_id, $user_id = null) {
        $crit = array ();

        if ($user_id) {
            $crit["conditions"] = "news_id = ?1 AND user_id = ?2";
            $crit["bind"] = array(1 => $news_id, 2 => $user_id);
        } else {
            $crit["conditions"] = "news_id = ?1";
            $crit["bind"] = array(1 => $news_id);
        }

        $ret = Comment::count($crit);

        return $ret;
    }

    public function getSource(){
        return "tb_comment";
    }
}
