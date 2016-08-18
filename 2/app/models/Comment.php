<?php
class Comment extends BaseModel {
    public $id;

    public $user_sign;

    public $news_sign;

    public $user_comment;

    public $create_time;

    
    public static function getAll($news_sign, $last_id, $pn, $prefer) {
        $crit = array (
            "limit" => 20,
        );
        
        if ($pn) {
            $crit["limit"] = $pn = $pn >= 20 ? 20 : $pn;
        }
        if ($prefer == "later") {
            $crit["order"] = "id DESC";
            if ($last_id) {
                $crit["conditions"] = "news_sign = ?1 AND id < ?2";
                $crit["bind"] = array(1 => $news_sign, 2=>$last_id);
            } else {
                $crit["conditions"] = "news_sign = ?1";
                $crit["bind"] = array(1 => $news_sign);
            }
        } else {
            $crit["order"] = "id";
            if ($last_id) {
                $crit["conditions"] = "news_sign = ?1 AND id > ?2";
                $crit["bind"] = array(1 => $news_sign, 2=>$last_id);
            } else {
                $crit["conditions"] = "news_sign = ?1";
                $crit["bind"] = array(1 => $news_sign);
            }
        }
        
        $comments = Comment::Find($crit);
        return $comments;
    }

    
    public static function getCount($news_sign, $user_sign = null) {
        $crit = array ();

        if ($user_sign) {
            $crit["conditions"] = "news_sign = ?1 AND user_sign = ?2";
            $crit["bind"] = array(1 => $news_sign, 2 => $user_sign);
        } else {
            $crit["conditions"] = "news_sign = ?1";
            $crit["bind"] = array(1 => $news_sign);
        }

        $ret = Comment::count($crit);

        return $ret;
    }

    public function getSource(){
        return "tb_comment";
    }
}
