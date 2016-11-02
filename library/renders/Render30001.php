<?php
/**
 * 
 * @file    Render30001.php
 * @authors Zhao Yulong (elysium.zyl@gmail.com)
 * @date    2016-10-27 21:35:42
 * @version $Id$
 */

class Render30001 extends BaseListRender {

    public function __construct($controller) {
        parent::__construct($controller);
    }

    public function render($models) {
        $ret = array();

        foreach ($models as $sign => $news_model) {
            $cell = $this->serializeNewsCell($news_model);
            if (count($cell["imgs"]) == 0) {
                continue;
            }
            $ret []= $cell;
        }

        return $ret;
    } 

    public function serializeNewsCell($news_model) {
        $commentCount = Comment::getCount($news_model->url_sign);
        $ret = array(
            "title" => $news_model->title,
            "news_id" => $news_model->url_sign,
            "source" => $news_model->source_name,
            "source_url" => $news_model->source_url,
            "public_time" => $news_model->publish_time,
            "likedCount" => $news_model->liked,
            "share_url" => sprintf(SHARE_TEMPLATE, urlencode($news_model->url_sign)),
            "views" => 1000,
            "commentCount" => $commentCount[$news_model->url_sign],
            "imgs" => array(),
            "videos" => array(),
            "tpl" => 12
            );


        $video = Videos::getByNewsSign($news_model->url_sign);
        if ($video) {
            $meta = json_decode($video->cover_meta, true);
            if (!$meta || 
                !is_numeric($meta["width"]) || 
                !is_numeric($meta["height"])) {
                continue;
            }

            $ow = $meta["width"];
            $oh = $meta["height"];

            if ($this->_net == "WIFI") {
                $quality = IMAGE_HIGH_QUALITY;
            } else if ($this->_net == "2G") {
                $quality = IMAGE_LOW_QUALITY;
            }else {
                $quality = IMAGE_NORMAL_QUALITY;
            }

            $url =  sprintf(VIDEO_COVER_PATTERN, 
                urlencode($video->cover_image_sign), $quality);

            $ret["imgs"][] = array(
                "src" => $url,
                "width" => $ow,
                "height" => $oh
            );

            $ret["videos"][] = array(
                "youtube_id" => $video->youtube_video_id,
                "width" => $ow,
                "height" => $oh,
                "duration" => $video->duration,
                "description" => $video->description,
                "display" => 0
            );
        }
        return $ret;
    }
}
