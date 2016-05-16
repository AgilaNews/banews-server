<?php
/**
 * @file   Feedback.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Mon May 16 16:09:38 2016
 * 
 * @brief  
 * 
 * 
 */

class Feedback extends BaseModel {
    public $id;

    public $user_id;

    public $device_id;

    public $feedback;

    public $email;

    public $ctime;

    public $problems;

    public function getSource(){
        return "tb_feedback";
    }
}
