<?php
class Comment extends BaseModel {
    public $id;

    public $user_sign;

    public $news_sign;

    public $user_comment;

    public $create_time;

    
    public static function getAll($news_sign, $last_id, $pn, $prefer = "later") {
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

    public static function getById($comment_id) {
        $cache = $this->di->get('cache');
        $key = CACHE_COMMENT_PREFIX . $comment_id;

        if ($cache) {
            $value = $cache->get($key);
            if ($value) {
                $comment = new $Comment();
                $comment->unserialize($value);
                return $comment;
            }
        }
        $comment_model = Comment::findFirst(array(
                                                  "condtions" => "id=?1",
                                                  "bind" => array(
                                                                  1 => $comment_id,
                                                                  )));
        if ($cache && $comment_model) {
            $cache->multi();
            $cache->set($key, $comment_model->serialize());
            $cache->expire($key, CACHE_COMMENT_TTL);
            $cache->exec();
        }

        return $comment_model;
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

    public function save($dataset = null, $whitelist = null) {
        $cache = $this->di->get('cache');
        $key = CACHE_COMMENT_FREQ_PREFIX . $this->user_sign;
        if ($cache) {
            $value = $cache->get($key);
            if ($value) {
                return false;
            }
        }

        $ret = parent::save($dataset, $whitelist);
        if ($ret) {
            $cache->multi();
            $cache->set($key, "1");
            $cache->expire($key, CACHE_COMMENT_FREQ_TTL);
            $cache->exec();
        }

        return $ret;
    }
    
    public function getSource(){
        return "tb_comment";
    }
}
