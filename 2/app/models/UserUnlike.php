<?php
/**
 * 
 * @file    UserUnlike.php
 * @authors Zhao Yulong (elysium.zyl@gmail.com)
 * @date    2016-11-17 21:16:44
 * @version $Id$
 */

class UserUnlike extends BaseModel {
    public $id;

    public $user_id;

    public $news_id;

    public $reason_type;

    public $reason_name;

    public $upload_time;

    public $devide_id;

    public function getSource() {
        return "tb_user_unlike";
    }
}