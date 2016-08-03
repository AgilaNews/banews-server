<?php


class PopularListPolicy extends BaseListPolicy {
    public function __construct($di) {
        parent::__construct($di);
    }

    public function sampling($channel_id, $device_id, $user_id, $pn, 
        $day_till_now, $prefer, array $options = array()) {
        $sentLst = $this->_cache->getDeviceSeen($device_id);

        // channel's top popular news list
        $channelPopularNewsLst = $this->_cache->getChannelTopPopularNews($channel_id); 
        $filterChannelPopularNewsLst = $this->sentFilter($sentLst, 
            $channelPopularNewsLst);         
        // channel's available news list
        $channelNewsLst = $this->_cache->getNewsOfchannel($channel_id, $day_till_now);
        $channelNewsLst = array_map(
                function($curObj) {
                    return $curObj["id"]; 
                }, $channelNewsLst
            );
        $filterChannelNewsLst = $this->sentFilter($sentLst, $channelNewsLst);
        
        if (!$filterChannelNewsLst) {
            return array();
        } else {
            if (count($filterChannelPopularNewsLst) >= $pn) {
                $retLst = array_slice($filterChannelPopularNewsLst, 0, $pn);
            } else {
                $retLst = array_slice($filterChannelNewsLst, 0, $pn);
            }
            return $retLst;
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
