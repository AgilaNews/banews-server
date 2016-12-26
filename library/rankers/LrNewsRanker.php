<?php

require_once (LIBRARY_PATH . "/pb/classify.php"); 

define ("ALG_NEWS_FEATURE_KEY", "ALG_NEWS_FEATURE_KEY_V2"); 
define ("MAX_RANKER_NEWS_CNT", 100);
define ("ORIGINAL_FEATURE_CNT", 93);
define ("HOUR", 60 * 60);
define ("MIN_FEATURE_VALUE", 0.001);
define ("ALG_NEWS_TOPIC_CNT", 80);
define ("ALG_NEWS_TOPIC_START_IDX", 14);

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
        for ($idx=0; $idx<ALG_NEWS_TOPIC_CNT; $idx++) {
            $this->FEATURE_MAPPING['TOPIC_' . $idx] = 
                    ALG_NEWS_TOPIC_START_IDX + $idx;  
        }
        $this->logger = $di->get("logger");
    }

    public function getRankerTag() {
        return "LrRanker";
    }

    protected function calcSpan($timestamp) {
        $now = time();
        if ($timestamp < $now) {
            return floatval($now - $timestamp) / HOUR; 
        }
        return MIN_FEATURE_VALUE;
    }

    public function getMetaFeatures($newsObjDct, &$featureDct) {
        foreach ($newsObjDct as $newsId => $newsObj) {
            $curFeatureDct = array();
            $title = $newsObj->title;
            $curFeatureDct['TITLE_LENGTH'] = count(explode(" ", $title));
            $content = $newsObj->json_text;
            $curFeatureDct['CONTENT_LENGTH'] = count(explode(" ", $content));
            $videos = NewsYoutubeVideo::getVideosOfNews($newsId);
            $curFeatureDct['VIDEO_COUNT'] = count($videos);
            $imgs = NewsImage::getImagesOfNews($newsId);
            $curFeatureDct['PICTURE_COUNT'] = count($imgs);
            $curFeatureDct['FETCH_TIMESTAMP_INTERVAL'] = 
                    $this->calcSpan($newsObj->fetch_time); 
            $curFeatureDct['POST_TIMESTAMP_INTERTVAL'] = 
                    $this->calcSpan($newsObj->publish_time);
            if (array_key_exists($newsId, $featureDct)) {
                foreach ($curFeatureDct as $key => $val) {
                    $featureDct[$newsId][$key] = $val;
                }
            } else {
                $featureDct[$newsId] = $curFeatureDct;
            }

        }
    }

    protected function getActionFeature($newsObjDct, &$featureDct) {
        $newsDisplayDct = News::batchGetActionFromCache(
            $newsObjDct, CACHE_FEATURE_DISPLAY_PREFIX);
        $newsClickDct = News::batchGetActionFromCache(
            $newsObjDct, CACHE_FEATURE_CLICK_PREFIX);
        $newsIdLst = array_keys($newsObjDct);
        $newsCommentDct = Comment::getCount($newsIdLst);
        foreach ($newsObjDct as $newsId => $newsObj) {
            $curFeatureDct = array();
            $displayCnt = MIN_FEATURE_VALUE;
            if (array_key_exists($newsId, $newsDisplayDct)) {
                $displayCnt = $newsDisplayDct[$newsId];
            }
            $displayCnt = max($displayCnt, MIN_FEATURE_VALUE);
            $curFeatureDct['HISTORY_DISPLAY_COUNT'] = 
                    $displayCnt;
            $likeCnt = $newsObj->liked;
            $curFeatureDct['HISTORY_LIKE_COUNT'] = 
                    $likeCnt;
            $curFeatureDct['HISTORY_LIKE_DISPLAY_RATIO'] = 
                    $likeCnt/$displayCnt;
            $clickCnt = 0;
            if (array_key_exists($newsId, $newsClickDct)) {
                $clickCnt = $newsClickDct[$newsId];
            }
            $curFeatureDct['HISTORY_READ_COUNT'] = 
                    $clickCnt;
            $curFeatureDct['HISTORY_READ_DISPLAY_RATIO'] = 
                    $clickCnt/$displayCnt;
            $commentCnt = 0;
            if (array_key_exists($newsId, $newsCommentDct)) {
                $commenCnt = $newsCommentDct[$newsId];
            }
            $curFeatureDct['HISTORY_COMMENT_COUNT'] = 
                    $commentCnt; 
            $curFeatureDct['HISTORY_COMMENT_DISPLAY_RATIO'] = 
                    $commentCnt/$displayCnt;
            if (array_key_exists($newsId, $featureDct)) {
                foreach ($curFeatureDct as $key => $val) {
                    $featureDct[$newsId][$key] = $val;
                }
            } else {
                $featureDct[$newsId] = $curFeatureDct;
            }
        }
    }

    protected function getNewsFeatures($newsObjDct) {
        $predictReq = new iface\PredictRequest();
        $featureDct = array(); 
        $filterNewsIdLst = array();
        $this->getMetaFeatures($newsObjDct, $featureDct);
        $this->getActionFeature($newsObjDct, $featureDct);
        foreach ($featureDct as $newsId => $curFeatureDct) {
            $formatedFeatureLst = array();
            $sampleObj = new iface\Sample();
            foreach ($this->FEATURE_MAPPING as $featureName => $featureIdx) {
                if (array_key_exists($featureName, $curFeatureDct)) {
                    $featureVal = $curFeatureDct[$featureName];
                    if (empty($featureVal)) {
                        continue;
                    }
                    $featureObj = new iface\Feature();
                    $featureObj->setIndex($featureIdx);
                    $featureObj->setValue(floatval($featureVal));
                    $sampleObj->addFeatures($featureObj);
                }
            }
            if (!$sampleObj->hasFeatures()) {
                continue;
            }
            $filterNewsIdLst[] = $newsId;
            $predictReq->addSamples($sampleObj);
        }
        return array($filterNewsIdLst, $predictReq, $featureDct);
    }

    protected function getScores($newsIdLst, $predictReq) {
        $lrRankerClient = $this->_di->get('lrRanker');
        if (empty($lrRankerClient)) {
            return array();
        }
        list($predictRes, $status) = 
                $lrRankerClient->Predict($predictReq)->wait();
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

    public function ranking($channelId, $deviceId, $newsObjDct, 
            $prefer, $sampleCnt, array $options=array()) {
        if (count($newsObjDct) > MAX_RANKER_NEWS_CNT) {
            $newsObjDct = array_slice($newsObjDct, 0, 
                MAX_RANKER_NEWS_CNT);
        }
        // user & news feature collection
        // TODO: Realtime features logging & aggregating
        list($filterNewsIdLst, $predictReq, $featureDct) = 
                $this->getNewsFeatures($newsObjDct);
        if (!$predictReq->hasSamples()) {
            return array();
        }

        // calculate news score according to Logistic Regression Model
        $newsScoArr = $this->getScores($filterNewsIdLst, $predictReq);

        // post-filter & sorting
        $sortedNewsObjLst = array();
        arsort($newsScoArr, SORT_NUMERIC);
        foreach ($newsScoArr as $newsId => $sco) {
            if (count($sortedNewsObjLst) > $sampleCnt) {
                break;
            }    
            $sortedNewsObjLst[$newsId] = $newsObjDct[$newsId];
        }
        return array($sortedNewsObjLst, $featureDct);
    }
}
