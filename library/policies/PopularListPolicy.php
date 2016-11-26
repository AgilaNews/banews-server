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
        } else if (count($news) < $pn) {
            return $news;
        } else {
            return array_slice($news, 0, $pn);
        }
    }

    protected function getReadyNews($channel_id, $day_till_now) {
        return $this->_cache->getChannelTopPopularNews($channel_id);
    }
}
