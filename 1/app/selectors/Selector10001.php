<?php
/**
 * @file   BaseNewsSelecter.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Thu Jun 30 13:49:16 2016
 * 
 * @brief  
 * 
 * 
 */
class Selector10001 extends BaseNewsSelector{

    private function getDeviceGroup($deviceId) { 
        $hashCode = hash('md5', $deviceId);
        $integerCode = base_convert($hashCode, 16, 10);
        if ($integerCode % 2 == 0) {
            return 0;
        } else {
            return 1;
        }
    }

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
