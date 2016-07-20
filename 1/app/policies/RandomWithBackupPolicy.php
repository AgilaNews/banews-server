<?php
define("CHANNEL_BACKUP_NEWS_PREFIX", "");
define("USER_BACKUP_NEWS_CURSOR", "");

class RandomWithBackupPolicy extends BaseListPolicy {
    public function __construct($di) {
        parent::__construct($di);
    }

    public function sampling($channel_id, $device_id, $user_id, $pn, 
                              $day_till_now=7, array $options = null) {
        $filter_news_lst = $this->getAllUnsent($channel_id, $device_id, null); 
        if (!$filter_news_lst) {
            $filter_news_lst = array();
        }
        $alr_cnt = count($filter_news_lst);
        if ($alr_cnt < $pn) {
            $append_news_lst = $this->_cache->getDeviceBackupNews(
                $device_id, $channel_id, $pn - $alr_cnt);
            foreach($append_news_lst as $cur_news) {
                array_push($filter_news_lst, $cur_news);
            }
        }
        if (count($filter_news_lst) > $pn) {
            $filter_news_lst = array_rand($filter_news_lst, $pn);
        }
        $res = array_map(
            function($key) use($filter_news_lst) {
                return $filter_news_lst[$key]["id"]; 
            }, $filter_news_lst
        );
        return $res;
    }
}
?>
