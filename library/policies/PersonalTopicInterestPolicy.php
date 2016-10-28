<?php
use Phalcon\DI;

define ('ALG_USER_TOPIC_KEY', 'ALG_USER_TOPIC_KEY');
define ('ALG_TOPIC_RATIO_KEY', 'ALG_TOPIC_RATIO_KEY');
define ('ALG_TOPIC_NEWS_SCO_KEY', 'ALG_TOPIC_NEWS_SCO_KEY');
define ('NEWS_TOPIC_QUEUE_PREFIX', 'ALG_TOPIC_NEWS_QUEUE_');
define ('SAMPLE_TOPIC_CNT', 2);
define ('TOPIC_NEWS_SELECT_CNT', 4);
define ('TOPIC_NEWS_SPAN', 2);
define ('ONE_DAY', 86400);
define ('TOPIC_NEWS_CANDIDATE_CNT', 200);

class PersonalTopicInterestPolicy extends BaseListPolicy {

    protected static function _getWholeTopicDis() {
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            $wholeTopicLst = $cache->hGetAll(ALG_TOPIC_RATIO_KEY);
            if ($wholeTopicLst) {
                $newWholeTopicLst = array();
                foreach ($wholeTopicLst as $topicIdx => $ratio) {
                    $newWholeTopicLst[$topicIdx] = floatval($ratio);
                }
                return $newWholeTopicLst;
            }
        }
        return array();
    }

    protected static function _getUserTopicDis($device_id) {
        $cache = DI::getDefault()->get('cache');
        if ($cache) {
            if ($cache->hExists(ALG_USER_TOPIC_KEY, $device_id)) {
                $valLst = json_decode($cache->hGet(ALG_USER_TOPIC_KEY, 
                                                   $device_id));
                if (count($valLst) == 2) {
                    $userTopicLst = $valLst[0];
                    $userTopicArr = array();
                    foreach ($userTopicLst as $curVals) {
                        $userTopicArr[$curVals[0]] = $curVals[1];
                    }
                    $clickCnt = $valLst[1];
                    return $userTopicArr;
                }
            } 
        }
        return array();
    }

    protected function combineTopicInterest($device_id) {
        $wholeTopicLst = self::_getWholeTopicDis();
        $userTopicLst = self::_getUserTopicDis($device_id);
        $topicIdxLst = array();
        $weightLst = array();
        foreach ($userTopicLst as $topicIdx => $score) {
            if (array_key_exists($topicIdx, $wholeTopicLst)) {
                $curWeight = $wholeTopicLst[$topicIdx] * $score;
                array_push($topicIdxLst, $topicIdx);
                array_push($weightLst, $curWeight);
            }
        }
        if (count($topicIdxLst) <= SAMPLE_TOPIC_CNT) {
            return $topicIdxLst;
        } else {
            $sampleTopicLst = SampleUtils::samplingWithoutReplace(
                    $topicIdxLst, $weightLst, SAMPLE_TOPIC_CNT);
            return $sampleTopicLst;
        }
    }

    protected function getNewsFromTopics($topicIdLst,
            $sentNewsLst) { 
        $cache = DI::getDefault()->get('cache');
        $now = time();
        $start = ($now - (TOPIC_NEWS_SPAN * ONE_DAY));
        $start = $start - ($start % ONE_DAY);
        $end = $now + (ONE_DAY - (($now + ONE_DAY) % ONE_DAY));
        $selectedNewsLst = array();
        foreach ($topicIdLst as $topicId) {
            $key = NEWS_TOPIC_QUEUE_PREFIX . $topicId;
            $curTopicNewsLst =  $cache->zRevRangeByScore($key, 
                    $end, $start, array("withscores"=>false));
            shuffle($curTopicNewsLst);
            $curTopicNewsLst = array_slice($curTopicNewsLst, 
                    0, TOPIC_NEWS_CANDIDATE_CNT);
            $newsWeightLst = $cache->hMGet(
                    ALG_TOPIC_NEWS_SCO_KEY, $curTopicNewsLst);
            arsort($newsWeightLst);
            foreach ($newsWeightLst as $newsId => $weight) {
                if (in_array($newsId, $selectedNewsLst) || 
                    in_array($newsId, $sentNewsLst)) {
                    continue;
                }
                if (count($selectedNewsLst) > TOPIC_NEWS_SELECT_CNT) {
                    break;
                }
                array_push($selectedNewsLst, $newsId);
            }
        }
        return $selectedNewsLst;
    }

    public function sampling($channel_id, $device_id, $user_id, $pn, 
        $day_till_now, $prefer, array $options = array()) {
        $sentLst = $this->_cache->getDeviceSeen($device_id);
        $sampleTopicLst = $this->combineTopicInterest($device_id); 
        $recNewsLst = $this->getNewsFromTopics($sampleTopicLst, 
                $sentLst);  
        if (count($recNewsLst) >= $pn) {
            $recNewsLst = array_slice($recNewsLst, 0, $pn);
        }
        return $recNewsLst;
    }
         
}

