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
        $options = array();
        if ($prefer == "later") {
            $options["long_tail_weight"] = 0;
        }
        $groupId = $this->getDeviceGroup($this->_device_id);
        $randomPolicy = new ExpDecayListPolicy($this->_di);
        if ($groupId == 0) {
            return $randomPolicy->sampling($this->_channel_id, $this->_device_id, 
                $this->_user_id, $sample_count, 3, $prefer, $options);
        } else {
            $popularPolicy = new PopularListPolicy($this->_di); 
            $options = array();
            if ($prefer == "later") {
                $options["long_tail_weight"] = 0;
            }
            $popularNewsCnt = max($sample_count - LATELY_NEWS_COUNT, 1);
            $popularNewsLst = $popularPolicy->sampling($this->_channel_id, 
                $this->_device_id, $this->_user_id, $popularNewsCnt, 3, 
                $prefer, $options);
            $randomNewsLst = $randomPolicy->sampling($this->_channel_id, 
                $this->_device_id, $this->_user_id, MAX_NEWS_COUNT, 3, 
                $prefer, $options);

            foreach($randomNewsLst as $randomNews) {
                if (count($popularNewsCnt) >= $sample_count) {
                    break;
                }
                if (in_array($randomNews, $popularNewsLst)) {
                    continue;
                }
                $popularNewsLst[] = $randomNews;
            }
            return $popularNewsLst;
        }
        
    }
}
