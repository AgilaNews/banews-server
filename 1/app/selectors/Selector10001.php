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
    public function sampling($sample_count, $prefer) {
        $policy = new ExpDecayListPolicy($this->_di);
        $options = array();
        if ($prefer == "later") {
            $options["long_tail_weight"] = 0;
        }

        return $policy->sampling($this->_channel_id, $this->_device_id, $this->_user_id,
                                 $sample_count, 3, $prefer, $options);
    }
}
