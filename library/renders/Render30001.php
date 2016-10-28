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
        $commentCount = Comment::getCount($news_model->id);
        $ret = array(
            "title" => $news_model->title,
            "news_id" => $news_model->url_sign,
            "source" => $news_model->source_name,
            "source_url" => $news_model->source_url,
            "public_time" => $news_model->publish_time,
            "likedCount" => $news_model->liked,
            "share_url" => sprintf(SHARE_TEMPLATE, urlencode($news_model->url_sign)),
            "views" => 1000,
            "commentCount" => $commentCount,
            "imgs" => array(),
            "videos" => array(),
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
            if ($this->_os == "ios") {
                $aw = (int) ($this->_screen_w  - 44);
            } else {
                $aw = (int) ($this->_screen_w * 11 / 12);
            }

            $ah = (int) min($this->_screen_h * 0.9, $aw * $oh / $ow);

            if ($this->_net == "WIFI") {
                $quality = IMAGE_HIGH_QUALITY;
            } else if ($this->_net == "2G") {
                $quality = IMAGE_LOW_QUALITY;
            }else {
                $quality = IMAGE_NORMAL_QUALITY;
            }

            $url =  sprintf(IMAGE_CHANNEL_IMG_PATTERN, 
                $video->cover_save_url, 
                $aw, $aw, $ah, $quality);

            $ret["imgs"][] = array(
                "src" => $url,
                "width" => $aw,
                "height" => $ah
            );

            $ret["videos"][] = array(
                "id" => $video->youtube_video_id,
                "width" => $aw,
                "height" => $ah,
                "duration" => $video->duration,
                "description" => $video->description
            );
        }
        return $ret;
    }
}
