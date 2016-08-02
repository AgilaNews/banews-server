<?php


class PopularListPolicy extends BaseListPolicy {
    public function __construct($di) {
        parent::__construct($di);
    }

    public function sampling($channel_id, $device_id, $user_id, $pn, 
        $day_till_now, $prefer, array $options = array()) {
        $channelPopularNewsLst = $this->_cache->channelTopPopularNews($channel_id); 
        $filter_news_lst = $this->getAllUnsent($channel_id, $device_id, null); 
        if (!$filter_news_lst) {
            return array();
        } else {
            $filter_news_lst = array_map(
                function($curObj) {
                    return $curObj["id"]; 
                }, $filter_news_lst
            );
            $filter_popular_lst = array();
            foreach($channelPopularNewsLst as $currentNews) {
                if (in_array($currentNews, $filter_news_lst)) {
                    continue;
                } else {
                    array_push($filter_popular_lst, $currentNews); 
                }
            }
            if (count($filter_popular_lst) >= $pn) {
                $retLst = array_slice($filter_popular_lst, 0, $pn);
            } else {
                $retLst = array_slice($filter_news_lst, 0, $pn);
            }
            return $retLst;
        }
    }
}
