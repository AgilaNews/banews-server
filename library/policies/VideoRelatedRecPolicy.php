<?php
/**
 * 
 * @file    VideoRelatedRecPolicy.php
 * @authors Zhao Yulong (elysium.zyl@gmail.com)
 * @date    2016-12-23 11:35:53
 * @version $Id$
 */

use Phalcon\DI;

class VideoRelatedRecPolicy extends BaseRecommendPolicy {
    public function __construct($di) {
        parent::__construct($di);
        $this->esClient = $di->get('elasticsearch');
        $this->logger = $di->get('logger');
    }

    protected function getRecommendNews($myself, $pn, $minThre=0.) {
        $video = Video::getByNewsSign($myself);
        $youtube_channel_id = $video->youtube_channel_id;

        $videos = Video::getVideosByAuthor($youtube_channel_id, $pn);
        return $videos;
    }

    public function sampling($channel_id, $device_id, $user_id, $myself, 
        $pn=3, $day_till_now=7, array $options=null) {
        $resLst = $this->getRecommendNews($myself, $pn, 0);
        return $resLst;
    }
}