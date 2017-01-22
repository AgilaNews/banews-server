<?php

require_once (LIBRARY_PATH . "/pb/classify.php"); 

define ("MAX_RANKER_NEWS_CNT", 100);
define ("MIN_FEATURE_VALUE", 0.001);
define ("FEATURE_GAP", "_");
define ("FEAFURE_SPACE_SIZE", "1000000");
define ("PRECISION", 6);

class LrNewsRanker extends BaseNewsRanker {

    public function __construct($di) {
        parent::__construct($di);
        $this->logger = $di->get("logger");
    }

    public function getRankerTag() {
        return "LrRanker";
    }

    public function bcHexDec($hex) {
        $dec = 0;
        $len = strlen($hex);
        for ($i=1; $i<=$len; $i++) {
            $dec = bcadd($dec, bcmul(strval(hexdec($hex[$i-1])), 
                bcpow('16', strval($len - $i))));
        }
        return $dec;
    }

    public function featureHash($featureName) {
        if (empty($featureName)) {
            return -1;
        }
        $hashHex = hash('sha1', $featureName);
        $hashDec = bcHexDec($hashHex);
        $hashMod = bcmod($hashDec, FEAFURE_SPACE_SIZE) + 1; 
        return $hashMod;
    }

    public function discreteGapFeatures($featureName, $value, $sepValLst) {
        sort($sepValLst, SORT_NUMERIC);
        foreach ($sepValLst as $idx=>$sepVal) {
            if ($value <= $sepVal) {
                return $featureName . FEATURE_GAP . $idx; 
            }
        }
        return $featureName . FEATURE_GAP . "MAX";
    }

    public function discreteIntFeatures($featureName, $value, $factor) {
        $intValue = intval($value * $factor);
        return $featureName . FEATURE_GAP . $intValue;
    }

    public function discreteBoolFeatures($featureName, $value) {
        if (empty($value)) {
            return $featureName . FEATURE_GAP . "1";
        } else {
            return $featureName . FEATURE_GAP . "0";
        }
    }

    protected function getTitleFeature($newsObj, &$featureDct, 
            &$discreteFeatureLst) {
        $title = strtolower($newsObj->title);
        $title = preg_replace("/[[:punct:]]+/", "", $title);
        $featureDct['TITLE'] = $newsObj->title;
        $titleWordLst = explode(" ", $newsObj->title);
        $titleCntFeature = $this->discreteGapFeatures('TITLE_COUNT',
            count($titleWordLst), array(5, 10, 15));
        $discreteFeatureLst[] = $titleCntFeature;
        foreach ($titleWordLst as $word) {
            $discreteFeatureLst[] = "WORD" . FEATURE_GAP . 
                strtolower($word);
        }
    }

    protected function getPictureFeature($newsObj, &$featureDct,
            &$discreteFeatureLst) {
        $imageLst = NewsImage::getImagesOfNews($newsObj->url_sign);
        $featureDct['PICTURE_COUNT'] = count($imageLst);
        $discreteFeatureLst[] = $this->discreteIntFeatures(
            'PICTURE_COUNT', count($imageLst), 1);
    }

    protected function getVideoFeature($newsObj, &$featureDct,
            &$discreteFeatureLst) {
        $videoLst = NewsYoutubeVideo::getVideosOfNews($newsObj->url_sign);
        $featureDct['VIDEO_COUNT'] = count($videoLst);
        $discreteFeatureLst[] = $this->discreteBoolFeatures(
            'PICTURE_COUNT', count($videoLst));
    }

    protected function getSourceFeature($newsObj, &$featureDct,
            &$discreteFeatureLst) {
        $featureDct['SOURCE'] = $newsObj->source_name; 
        $sourceFeature = 'SOURCE' . FEATURE_GAP . 
            str_replace(' ', '-', $newsObj->source_name);
        $discreteFeatureLst[] = $sourceFeature;
    }

    protected function getChannelFeature($newsObj, &$featureDct,
            &$discreteFeatureLst) {
        $featureDct['CHANNEL_ID'] = $newsObj->channel_id;
        $channelFeature = 'CHANNEL_ID' . FEATURE_GAP . 
            $newsObj->channel_id;
        $discreteFeatureLst[] = $channelFeature;
    }

    protected function getDisplayFeature($newsObj, $newsDisplayDct, 
            &$featureDct, &$discreteFeatureLst) {
        $displayCnt = MIN_FEATURE_VALUE;
        if (array_key_exists($newsObj->url_sign, $newsDisplayDct)) {
            $displayCnt = max($newsDisplayDct[$newsObj->url_sign], 
                $displayCnt);
        }
        $featureDct['HISTORY_DISPLAY_COUNT'] = $displayCnt;
        $discreteFeatureLst[] = $this->discreteGapFeatures(
            'HISTORY_DISPLAY_COUNT', $displayCnt, 
            array(100, 1000, 5000, 10000, 50000, 100000));
        return $displayCnt;
    }

    protected function getReadFeature($newsObj, $displayCnt,
            $newsClickDct, &$featureDct, &$discreteFeatureLst) {
        $clickCnt = 0;
        if (array_key_exists($newsObj->url_sign, $newsClickDct)) {
            $clickCnt = $newsClickDct[$newsObj->url_sign];
        }
        $clickRatio = min(1.0, round($clickCnt/$displayCnt, PRECISION));
        $featureDct['HISTORY_READ_COUNT'] = $clickCnt;
        $featureDct['HISTORY_READ_DISPLAY_RATIO'] = $clickRatio;
        $discreteFeatureLst[] = $this->discreteGapFeatures(
            'HISTORY_READ_COUNT', $clickCnt, 
            array(100, 1000, 5000, 10000));
        $discreteFeatureLst[] = $this->discreteIntFeatures(
            'HISTORY_READ_DISPLAY_RATIO', $clickRatio, 1000);
    }

    protected function getCommentFeature($newsObj, $displayCnt,
            $newsCommentDct, &$featureDct, &$discreteFeatureLst) {
        $commentCnt = 0;
        if (array_key_exists($newsObj->url_sign, $newsCommentDct)) {
            $commentCnt = $newsCommentDct[$newsObj->url_sign];
        }
        $commentRatio = min(1.0, round($commentCnt/$displayCnt, PRECISION));
        $featureDct['HISTORY_COMMENT_COUNT'] = $commentCnt;
        $featureDct['HISTORY_COMMENT_DISPLAY_RATIO'] = $commentRatio;
        $discreteFeatureLst[] = $this->discreteGapFeatures(
            'HISTORY_COMMENT_COUNT', $commentCnt, 
            array(5, 10, 20, 50, 100));
        $discreteFeatureLst[] = $this->discreteIntFeatures(
            'HISTORY_COMMENT_DISPLAY_RATIO', $commentRatio, 1000);
    }

    protected function getLikeFeature($newsObj, $displayCnt,
            &$featureDct, &$discreteFeatureLst) {
        $likeCnt = $newsObj->liked;
        $likeRatio = min(1.0, round($likeCnt/$displayCnt, PRECISION));
        $featureDct['HISTORY_LIKE_COUNT'] = $likeCnt;
        $featureDct['HISTORY_LIKE_DISPLAY_RATIO'] = $likeRatio;
        $discreteFeatureLst[] = $this->discreteGapFeatures(
            'HISTORY_LIKE_COUNT', $likeCnt, 
            array(10, 50, 100, 500, 1000));
        $discreteFeatureLst[] = $this->discreteIntFeatures(
            'HISTORY_LIKE_DISPLAY_RATIO', $likeRatio, 1000);
    }

    protected function extractNewsFeatures($newsObjDct) {
        $newsDisplayDct = News::batchGetActionFromCache($newsObjDct, 
            CACHE_FEATURE_DISPLAY_PREFIX);
        $newsClickDct = News::batchGetActionFromCache($newsObjDct, 
            CACHE_FEATURE_CLICK_PREFIX);
        $newsIdLst = array_keys($newsObjDct);
        $newsCommentDct = Comment::getCount($newsIdLst);
        $originalFeatureDct = array();
        $discreteFeatureDct = array();
        foreach ($newsObjDct as $newsId => $newsObj) {
            $curFeatureDct = array();
            $curDiscreteFeatureLst = array();
            $this->getTitleFeature($newsObj, $curFeatureDct, 
                $curDiscreteFeatureLst);
            $this->getPictureFeature($newsObj, $curFeatureDct,
                $curDiscreteFeatureLst);
            $this->getVideoFeature($newsObj, $curFeatureDct,
                $curDiscreteFeatureLst);
            $this->getSourceFeature($newsObj, $curFeatureDct,
                $curDiscreteFeatureLst);
            $this->getChannelFeature($newsObj, $curFeatureDct,
                $curDiscreteFeatureLst);
            $displayCnt = $this->getDisplayFeature($newsObj, 
                $newsDisplayDct, $curFeatureDct, $curDiscreteFeatureLst);
            $this->getReadFeature($newsObj, $displayCnt, 
                $newsClickDct, $curFeatureDct, $curDiscreteFeatureLst);
            $this->getCommentFeature($newsObj, $displayCnt, 
                $newsCommentDct, $curFeatureDct, $curDiscreteFeatureLst);
            $this->getLikeFeature($newsObj, $displayCnt, 
                $curFeatureDct, $curDiscreteFeatureLst);
            if (array_key_exists($newsId, $originalFeatureDct)) {
                foreach ($curFeatureDct as $key => $val) {
                    $originalFeatureDct[$newsId][$key] = $val;
                }
                foreach ($curDiscreteFeatureLst as $featureName) {
                    $discreteFeatureDct[$newsId][] = $featureName;
                }

            } else {
                $originalFeatureDct[$newsId] = $curFeatureDct;
                $discreteFeatureDct[$newsId] = $curDiscreteFeatureLst;
            }
        }
        return array($originalFeatureDct, $discreteFeatureDct);
    }

    protected function generateNewsSamples($newsObjDct) {
        $predictReq = new iface\PredictRequest();
        $filterNewsIdLst = array();
        // extract features of news, discrete feature through one hot
        $resLst = $this->extractNewsFeatures($newsObjDct); 
        $originalFeatureDct = $resLst[0];
        $discreteFeatureDct = $resLst[1]; 
        // hashing feature by sha1 method, form grpc sample
        foreach ($discreteFeatureDct as $newsId => $featureNameLst) {
            $sampleObj = new iface\Sample();
            $featureIdxLst = array();
            foreach ($featureNameLst as $featureName) {
                $featureIdx = $this->featureHash($featureName);  
                if ($featureIdx <= 0) {
                    continue;
                }
                $featureIdxLst[] = $featureIdx;
            }
            sort($featureIdxLst, SORT_NUMERIC);
            foreach ($featureIdxLst as $featureIdx) {
                $featureObj = new iface\Feature();
                $featureObj->setIndex($featureIdx);
                $featureObj->setValue(1.0);
                $sampleObj->addFeatures($featureObj);
            }
            if (!$sampleObj->hasFeatures()) {
                continue;
            }
            $filterNewsIdLst[] = $newsId;
            $predictReq->addSamples($sampleObj);
        }
        return array($filterNewsIdLst, $predictReq, $originalFeatureDct);
    }

    protected function getScores($newsIdLst, $predictReq) {
        $lrRankerClient = $this->di->get('lrRanker');
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
                $this->generateNewsSamples($newsObjDct);
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
