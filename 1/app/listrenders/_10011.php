<?php
define("MAX_IMG_SEND_COUNT", 12);
define("MIN_IMG_SENT_COUNT", 10);
define('IMAGE_CHANNEL_PATTERN', "http://" . IMAGE_SERVER_NAME . '/' . IMAGE_PREFIX . "/%s.jpg?p=s=%dX_w|c=%dX%d@0X0|q=" . IMAGE_QUALITY);

class _10011 extends BaseListRender {
    public function __construct($cid, $did, $di) {
        parent::__construct($cid, $did, $di);
    }

    public function getPolicyTag(){
        return "random";
    }

    public function render($dispatch_id, $prefer) {
        $policy = new RandomListPolicy($this->_di);
        $required = mt_rand(MIN_IMG_SENT_COUNT, MAX_IMG_SEND_COUNT);

        $selected_news_list = $polciy->sampling($this->_channel_id, $this->_device_id,
                                                null, $required, $prefer);
        $ret = array($dispatch_id => array(), "dispatched" => array());
        
        $models = News::batchGet($selected_news_list);
        foreach ($models as $sign => $news_model) {
            if ($news_model && $news_model->is_visible == 1) {
                $ret[$dispatch_id][] = $cell;
                $ret["dispatched"][] = $sign;
            }
        }

        $policy->setDeviceSent($this->_device_id, $ret["dispatched"]);

        return $ret;
    } 

    protected function serializeNewsCell($news_model){
        $imgs = NewsImage::getImagesOfNews($news_model->url_sign);
        
        $ret = array(
            "title" => $news_model->title,
            "news_id" => $news_model->url_sign,
            "source" => $news_model->source_name,
            "source_url" => $news_model->source_url,
            "public_time" => $news_model->publish_time,
        );

        $ret["tpl"] = NEWS_LIST_TPL_RAW_IMG;
        foreach($imgs as $img) {
            if (!$img || $img->is_deadlink == 1 || !$img->meta) {
                continue;
            }

            if ($img->origin_url) {
                $meta = json_decode($img->meta, true);
                if (!$meta || !$meta["width"] || !$meta["height"]) {
                    continue;
                }
                $ow = $meta["width"];
                $oh = $meta["height"];
                $aw = (int) ($screen_width * 11 / 12);
                $ah = (int) min($screen_height * 0.9, $aw * $oh / $ow);
                $url =  sprintf(IMAGE_CHANNEL_PATTERN, 
                                $img->url_sign, 
                                $aw, $aw, $ah);
                $ret["imgs"][] = array(
                    "src" => $url, 
                    "height" => $ah, 
                    "width" => $aw);
            }
        }

        return $ret;
    }
}
