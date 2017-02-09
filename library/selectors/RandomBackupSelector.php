<?php
define("MIN_IMG_SEND_COUNT", 10);
define("MAX_IMG_SEND_COUNT", 12);

class RandomBackupSelector extends BaseNewsSelector {
    public function getPolicyTag() {
        return "pure_random"; 
    }

    public function getPolicy() {
        if (!isset($this->policy)) {
            $this->policy = new RandomWithBackupPolicy($this->di); 
        }
        return $this->policy;
    }

    public function removeDup($models) {
        $ret = array();
        $uniq = array();

        foreach ($models as $sign => $news_model) { 
            if (array_key_exists($news_model->url_sign, $uniq) 
            ) {
                //url sign dup continue
                continue;
            }

            $ret [$sign] = $news_model;
            $uniq[$news_model->url_sign] = $news_model;
        }

        return $ret;
    }

    public function sampling($sampling_count, $prefer) {
        return $this->getPolicy()->sampling($this->channel_id, $this->device_id,
                                 $this->user_id, $sampling_count, null, $prefer);
    }

    public function select($prefer) {
        $required = mt_rand(MIN_IMG_SEND_COUNT, MAX_IMG_SEND_COUNT);
        $selected_news_list = $this->sampling($required, $prefer);
        $models = News::batchGet($selected_news_list);
        $models = $this->removeInvisible($models);
        $models = $this->removeDup($models);
        
        $ret = array();
        $filter = array();
        for ($i = 0; $i < count($selected_news_list); $i++) {
            if (array_key_exists($selected_news_list[$i], $models)) {
                $ret []= $models[$selected_news_list[$i]];
                $filter []= $models[$selected_news_list[$i]]->url_sign;
                if (count($ret) >= $required) {
                    break;
                }
            }
        }
        
        $this->setDeviceSeenToBF($filter);
        return $ret;
    }
}
