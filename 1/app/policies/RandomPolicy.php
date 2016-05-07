<?php
class RandomPolicy extends BasePolicy {
    public function __construct($di) {
        parent::__construct($di);
    }

    public function sampling($channel_id, $device_id, $user_id, $pn = 10, array $options = null) {
        $sent = $this->redis->getDeviceSeen($device_id);
        $ready_news_list = $this->redis->getNewsOfchannel($channel_id);
        $valid_news_list = array();

        foreach ($ready_news_list as $ready_news) {
            $ready_news = json_decode($ready_news, true);
            if ($ready_news) {
                if (!in_array($ready_news["id"], $sent)) {
                    $valid_news_list []= $ready_news["id"];
                } 
            }
        }

        $ret = array_map(
            function($key) use ($valid_news_list) {
                return $valid_news_list[$key];
                },
                array_rand($valid_news_list, $pn)
            );

        $this->logPolicy(sprintf("[RAND_POLICY] [w:%d][s:%d][d:%d]", count($ready_news_list), count($valid_news_list), count($ret)));
        return $ret;
    }  
}
