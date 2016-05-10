<?php
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
