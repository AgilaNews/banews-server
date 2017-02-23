<?php
/**
 * 
 * @file 
 * @authors Zhao Yulong (elysium.zyl@gmail.com)
 * @date    2017-02-22 15:54:18
 * @version $Id$
 */

use Phalcon\DI;

define('ALG_USER_YOUTUBE_CHANNEL_KEY', 'ALG_USER_YOUTUBE_CHANNEL_KEY');
define('ALG_YOUTUBE_CHANNEL_GRAVITY_KEY', 'ALG_YOUTUBE_CHANNEL_GRAVITY_KEY');
define('ALG_YOUTUBE_CHANNEL_RATIO_KEY', 'ALG_YOUTUBE_CHANNEL_RATIO_KEY');
define('SAMPLE_CHANNEL_CNT', 10);
define('MAX_VIDEO_ONE_CHANNEL', 2);

class PersonalVideoInterestPolicy extends BaseListPolicy {
    private function getUserYoutubeInterests($device_id) {
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            if ($cache->hExists(ALG_USER_YOUTUBE_CHANNEL_KEY, $device_id)) {
                $valLst = json_decode($cache->hGet(ALG_USER_YOUTUBE_CHANNEL_KEY,
                                                   $device_id));
                if (count($valLst) == 2) {
                    $userTopicLst = $valLst[0];
                    $userTopicArr = array();
                    foreach ($userTopicLst as $curVals) {
                        $userTopicArr[$curVals[0]] = $curVals[1];
                    }
                    $clickCnt = $valLst[1];
                    return array($userTopicArr, $clickCnt);
                }
            }
        }
        return array();
    }

    private function getYoutubeChannelRatio(){
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            $youtubeChannelRatioLst = $cache->hGetAll(ALG_YOUTUBE_CHANNEL_RATIO_KEY);
            if ($youtubeChannelRatioLst) {
                $newYoutubeChannelRatioLst = array();
                foreach ($youtubeChannelRatioLst as $youtubeChannelId => $ratio) {
                    $newYoutubeChannelRatioLst[$youtubeChannelId] = floatval($ratio);
                }
                return $newYoutubeChannelRatioLst;
            }
        }
        return array();
    }

    private function samplingUserYoutubeChannel($userYoutubeInterests) {
        $youtubeChannelRatioLst = $this->getYoutubeChannelRatio();
        $userYoutubeChannelLst = $userYoutubeInterests[0];
        $totalClickCnt = $userYoutubeInterests[1];
        $cache = DI::getDefault()->get('cache');
        $gravity = floatval($cache->get(ALG_YOUTUBE_CHANNEL_GRAVITY_KEY));

        $youtubeChannelLst = array();
        $weightLst = array();
        foreach ($youtubeChannelRatioLst as $youtubeChannelId => $score) {
            if (array_key_exists($youtubeChannelId, $userYoutubeChannelLst)) {
                $curWeight = $userYoutubeChannelLst[$youtubeChannelId] * $score;
            } else {
                $curWeight = ($gravity / ($gravity + $totalClickCnt)) * $score;
            }

            array_push($youtubeChannelLst, $youtubeChannelId);
            array_push($weightLst, $curWeight);
        }

        if (count($youtubeChannelLst) <= SAMPLE_CHANNEL_CNT) {
            return $youtubeChannelLst;
        } else {
            $sampleChannelLst = SampleUtils::samplingWithoutReplace(
                $youtubeChannelLst, $weightLst, SAMPLE_CHANNEL_CNT);
            return $sampleChannelLst;
        }
    }

    private function getUserInterestVideos($device_id) {
        $userYoutubeInterests = $this->getUserYoutubeInterests($device_id);
        if (!$userYoutubeInterests) {
            return array();
        }

        $userYoutubeChannels = $this->samplingUserYoutubeChannel($userYoutubeInterests);
        $tmp = array();
        foreach ($userYoutubeChannels as $youtubeChannel) {
            $videos = Video::getVideosByAuthor($youtubeChannel);
            shuffle($videos);
            $tmp = array_merge($tmp, array_slice($videos,0, MAX_VIDEO_ONE_CHANNEL));
        }
        $ret = array();
        foreach ($tmp as $video_id) {
            $ret[] = array("id" => $video_id, "weight"=>1);
        }
        return $ret;
    }

    public function sampling($channel_id, $device_id, $user_id, $pn,
        $day_till_now, $prefer, array $options = array()) {
        $userInterestVideos = $this->getUserInterestVideos($device_id);
        $unsentVideos = $this->tryBloomfilter($channel_id, $device_id, $userInterestVideos);
        
        $ret = array();
        foreach($unsentVideos as $video) {
            $ret[] = $video['id'];
        }

        if(count($ret) > $pn) {
            shuffle($ret);
            $unsentVideos = array_slice($ret, 0, $pn);
        }
        return $ret;
    }
}