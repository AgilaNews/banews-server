<?php
class Selector20001 extends Selector10001 {

    public function getPolicyTag(){
        $groupId = $this->getDeviceGroup($this->_device_id);
        if ($groupId == 0) {
            return "expdecay";
        } else {
            return 'popularRanking';
        }
    }
    
    public function sampling($sample_count, $prefer) {
        $groupId = $this->getDeviceGroup($this->_device_id);
        if ($groupId == 0) {
            $policy = new ExpDecayListPolicy($this->_di);
        } else {
            $policy = new PopularListPolicy($this->_di); 
        }
        $options = array();
        if ($prefer == "later") {
            $options["long_tail_weight"] = 0;
        }

        return $policy->sampling($this->_channel_id, $this->_device_id, 
            $this->_user_id, $sample_count, 3, $prefer, $options);
    }
}
