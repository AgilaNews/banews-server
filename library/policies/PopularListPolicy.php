<?php

class PopularListPolicy extends BaseListPolicy {
    public function __construct($di) {
        parent::__construct($di);
    }

    public function sampling($channel_id, $device_id, $user_id, $pn, 
        $day_till_now, $prefer, array $options = array()) {
        // channel's top popular news list
        $channelPopularNewsLst = $this->_cache->getChannelTopPopularNews($channel_id);
        
        if ($channel_id == "30001") {
            $bf_service = $this->_di->get("bloomfilter");
            $filterChannelPopularNewsLst = $bf_service->filter(
                                                               BloomFilterService::FILTER_FOR_VIDEO,
                                                               $channelPopularNewsLst,
                                                               function($news_id) use ($device_id) {
                                                                   return $device_id . "_" . $news_id;
                                                               }
                                                               );
        } else {
            $sentLst = $this->_cache->getDeviceSeen($device_id);
            $filterChannelPopularNewsLst = $this->sentFilter($sentLst, 
                                                             $channelPopularNewsLst);
        }
        if (!$filterChannelPopularNewsLst or (count($filterChannelPopularNewsLst) < $pn)) {
            return array();
        } else {
            return array_slice($filterChannelPopularNewsLst, 0, $pn);        
        }

    }

    protected function sentFilter($sentNewsLst, $newsLst) {
        $filterNewsLst = array();
        foreach ($newsLst as $news) {
            if (!in_array($news, $sentNewsLst)) {
                array_push($filterNewsLst, $news); 
            }
        }
        return $filterNewsLst;
    }

}
