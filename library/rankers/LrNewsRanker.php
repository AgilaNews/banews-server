<?php

require_once (LIBRARY_PATH . "/pb/classify.php"); 

define ("ALG_NEWS_FEATURE_KEY", "ALG_NEWS_FEATURE_KEY"); 
define ("MAX_RANKER_NEWS_CNT", 100);
define ("ORIGINAL_FEATURE_CNT", 13);
define ("HOUR", 60 * 60);
define ("MIN_FEATURE_VALUE", 0.001);

class LrNewsRanker extends BaseNewsRanker {

    public function __construct($di) {
        parent::__construct($di);
        $this->FEATURE_MAPPING = array(
            "HISTORY_DISPLAY_COUNT" => 1,
            "HISTORY_READ_COUNT" => 2,
            "HISTORY_LIKE_COUNT" => 3,
            "HISTORY_COMMENT_COUNT" => 4,
            "HISTORY_READ_DISPLAY_RATIO" => 5,
            "HISTORY_LIKE_DISPLAY_RATIO" => 6,
            "HISTORY_COMMENT_DISPLAY_RATIO" => 7,
            "PICTURE_COUNT" => 8,
            "VIDEO_COUNT" => 9,
            "TITLE_LENGTH" => 10,
            "CONTENT_LENGTH" => 11,
            "FETCH_TIMESTAMP_INTERVAL" => 12,
            "POST_TIMESTAMP_INTERTVAL" => 13
        );
    }

    public function getRankerTag() {
        return "LrRanker";
    }

    protected function _formatFeatures($originalFeatureLst) {
        // transfer original features to sparse vector input format
        if (count($originalFeatureLst) != ORIGINAL_FEATURE_CNT) {
            return array();
        }
        $transferFeatureArr = array();
        if (!empty($originalFeatureLst[0])) {
            $transferFeatureArr["HISTORY_DISPLAY_COUNT"] = $originalFeatureLst[0];
        }
        if (!empty($originalFeatureLst[1])) {
            $transferFeatureArr["HISTORY_READ_COUNT"] = $originalFeatureLst[1];
        }
        if (!empty($originalFeatureLst[2])) {
            $transferFeatureArr["HISTORY_LIKE_COUNT"] = $originalFeatureLst[2];   
        }
        if (!empty($originalFeatureLst[3])) {
            $transferFeatureArr["HISTORY_COMMENT_COUNT"] = $originalFeatureLst[3];   
        }
        if (!empty($originalFeatureLst[4])) {
            $transferFeatureArr["HISTORY_READ_DISPLAY_RATIO"] = $originalFeatureLst[4];   
        }
        if (!empty($originalFeatureLst[5])) {
            $transferFeatureArr["HISTORY_LIKE_DISPLAY_RATIO"] = $originalFeatureLst[5];   
        }
        if (!empty($originalFeatureLst[6])) {
            $transferFeatureArr["HISTORY_COMMENT_DISPLAY_RATIO"] =
                $originalFeatureLst[6];   
        }
        if (!empty($originalFeatureLst[7])) {
            $transferFeatureArr["TITLE_LENGTH"] = $originalFeatureLst[7];   
        }
        if (!empty($originalFeatureLst[8])) {
            $transferFeatureArr["PICTURE_COUNT"] = $originalFeatureLst[8];   
        }
        if (!empty($originalFeatureLst[9])) {
            $transferFeatureArr["VIDEO_COUNT"] = $originalFeatureLst[9];   
        }
        if (!empty($originalFeatureLst[10])) {
            $transferFeatureArr["CONTENT_LENGTH"] = $originalFeatureLst[10];   
        }
        $curTimestamp = time();
        if (!empty($originalFeatureLst[11])) {
            $pubTimestamp = $originalFeatureLst[11];   
            if ($pubTimestamp > $curTimestamp) {
                $transferFeatureArr["FETCH_TIMESTAMP_INTERVAL"] = 
                    MIN_FEATURE_VALUE;
            } else {
                $transferFeatureArr["FETCH_TIMESTAMP_INTERVAL"] = 
                   floatval($curTimestamp - $pubTimestamp) / HOUR; 
            }
        }
        if (!empty($originalFeatureLst[12])) {
            $posTimestamp = $originalFeatureLst[12];   
            if ($posTimestamp > $curTimestamp) {
                $transferFeatureArr["POST_TIMESTAMP_INTERTVAL"] = 
                    MIN_FEATURE_VALUE;
            } else {
                $transferFeatureArr["POST_TIMESTAMP_INTERTVAL"] = 
                   floatval($curTimestamp - $posTimestamp) / HOUR; 
            }
        }
        $resArr = array();
        foreach ($this->FEATURE_MAPPING as $featureName => $featureIdx) {
            if (array_key_exists($featureName, $transferFeatureArr)) {
                $resArr[$featureIdx] = $transferFeatureArr[$featureName];
            }
        }
        return $resArr;
    }

    protected function _getNewsFeatureFromCache($newsIdLst) {
        $cache = $this->_di->get('cache');
        $predictReq = new iface\PredictRequest();
        $filterNewsIdLst = array();
        if ($cache) {
            $newsFeatureArr = $cache->hMGet(ALG_NEWS_FEATURE_KEY, 
                $newsIdLst);
            foreach ($newsFeatureArr as $newsId => $featureStr) {
                if (!$featureStr) {
                    continue;
                }
                $sampleObj = new iface\Sample();
                $originalFeatureLst = json_decode($featureStr);
                $featureArr = $this->_formatFeatures($originalFeatureLst);
                foreach ($featureArr as $featureIdx => $featureVal) {
                    if (empty($featureVal)) {
                        continue;
                    }
                    $featureObj = new iface\Feature();
                    $featureObj->setIndex($featureIdx);
                    $featureObj->setValue(floatval($featureVal));
                    $sampleObj->addFeatures($featureObj);
                }
                if (!$sampleObj->hasFeatures()) {
                    continue;
                }
                $filterNewsIdLst[] = $newsId;
                $predictReq->addSamples($sampleObj);
            }
        }
        return array($filterNewsIdLst, $predictReq);
    }

    protected function getScores($newsIdLst, $predictReq) {
        $lrRankerClient = $this->_di->get('lrRanker');
        if (empty($lrRankerClient)) {
            return array();
        }
        list($predictRes, $status) = $lrRankerClient->Predict($predictReq)->wait();
        if ($status->code != 0) {
            $this->logger->warning("get classify error:" . $status->code . 
                ":" . json_encode($status->details, true));
            return array();
        }
        if (!$predictRes->hasSamples()) {
            return array();
        }

        $newsIdScoArr = array();
        if (count($newsIdLst) != count($predictRes->getSamplesList())) {
            return array();
        }
        for ($idx=0; $idx<count($newsIdLst); $idx++) {
            $newsId = $newsIdLst[$idx];
            $probOfSample = $predictRes->getSamples($idx); 
            if (!$probOfSample->hasProbs()) {
                $newsIdScoArr[$newsId] = 0.0;
            } else {
                $newsIdScoArr[$newsId] = $probOfSample->getProbs();
            }
        }
        return $newsIdScoArr;
    }

    public function ranking($channelId, $deviceId, $newsIdLst, 
        $prefer, $newsCnt, array $options=array()) {
        if (count($newsIdLst) > MAX_RANKER_NEWS_CNT) {
            $newsIdLst = array_slice($newsIdLst, 0, 
                MAX_RANKER_NEWS_CNT);
        }
        // user & news feature collection
        // TODO: Realtime features logging & aggregating
        list($filterNewsIdLst, $predictReq) = 
            $this->_getNewsFeatureFromCache($newsIdLst);
        if (!$predictReq->hasSamples()) {
            return array();
        }

        // calculate news score according to Logistic Regression Model
        $newsScoArr = $this->getScores($filterNewsIdLst, $predictReq);

        // post-filter & sorting
        arsort($newsScoArr, SORT_NUMERIC);
        $recNewsIdLst = array_keys($newsScoArr);
        if (count($recNewsIdLst) > $newsCnt) {
            return array_slice($recNewsIdLst, 0, $newsCnt);
        } else  {
            return $recNewsIdLst;
        }
    }
}
