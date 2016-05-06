<?php
class Channel extends BaseModel {
    public $channel_id;

    public $parent_id;

    public $name;

    public $is_visible;

    public function getChannelById($id, $columns = null) {
        $crit = array (
            "conditions" => "id => ?1",
            "bind" => array(1 => $id),
        );

        if ($column) {
            $crit["columns"] = $columns;
        }

        return Channel::findFirst($crit);
    }

    public function getSource(){
        return "tb_channel";
    }
}
