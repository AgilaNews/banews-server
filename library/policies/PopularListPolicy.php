<?php

class PopularListPolicy extends BaseListPolicy {
    public function __construct($di) {
        parent::__construct($di);
    }

    public function sampling($channel_id, $device_id, $user_id, $pn, 
        $day_till_now, $prefer, array $options = array()) {
        $news = $this->getAllUnsent($channel_id, $device_id, $day_till_now);
        if (!$news) {
            return array();
        }
        $news_ids = array_map(function($news) {return $news["id"];}, $news);

        if (!$news_ids) {
            return array();
        } else if (count($news_ids) < $pn) {
            return $news_ids;
        } else {
            return array_slice($news_ids, 0, $pn);
        }
    }

    protected function getReadyNews($channel_id, $day_till_now) {
        return $this->cache->getChannelTopPopularNews($channel_id);
    }
}
