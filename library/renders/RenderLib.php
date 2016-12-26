<?php
/**
 * 
 * @file    RenderLib.php
 * @authors Zhao Yulong (elysium.zyl@gmail.com)
 * @date    2016-12-04 17:45:14
 * @version $Id$
 */

class RenderLib {
    public static function GetPublicData($news_model) {
        $ret = array(
            "title" => $news_model->title,
            "news_id" => $news_model->url_sign,
            "source" => $news_model->source_name,
            "source_url" => $news_model->source_url,
            "public_time" => $news_model->publish_time,
            "likedCount" => $news_model->liked,
            "share_url" => sprintf(SHARE_TEMPLATE, urlencode($news_model->url_sign)),
            "commentCount" => 0,
            "imgs" => array(),
            "videos" => array(),
            );
        return $ret;
    }

    public static function GetImageQuality($net) {
        if ($net == "WIFI") {
            return IMAGE_HIGH_QUALITY;
        } else if ($net == "2G") {
            return IMAGE_LOW_QUALITY;
        } else {
            return IMAGE_NORMAL_QUALITY;
        }
    }

    public static function ImageRender($net, $url_sign, $meta, $large) {
        $quality = RenderLib::GetImageQuality($net);

        $cell = array(
            "width" => $meta["width"], 
            "height" => $meta["height"], 
            );
        
        if ($large) {
            $cell["pattern"] = sprintf(LARGE_CHANNEL_IMG_PATTERN, $url_sign, "{w}", "{h}", $quality);
            $cell["src"] = sprintf(LARGE_CHANNEL_IMG_PATTERN, urlencode($url_sign), "660", "410", $quality);
        } else {
            $cell["pattern"] = sprintf(BASE_CHANNEL_IMG_PATTERN, $url_sign, "{w}", "{h}", $quality);
            $cell["src"] = sprintf(BASE_CHANNEL_IMG_PATTERN, urlencode($url_sign), "225", "180", $quality);
        }

        return $cell;
    }

    protected static function LargeImageScale($meta, $screen_w, $screen_h, $os) {
        $ow = $meta["width"];
        $oh = $meta["height"];

        if ($os == "ios") {
            $aw = (int) ($screen_w  - 44);
        } else {
            $aw = (int) ($screen_w * 11 / 12);
        }
        $ah = (int) min($screen_h * 0.9, $aw * $oh / $ow);
        return array($aw, $ah);
    }

    public static function LargeImageRender($net, $url_sign, $meta, $screen_w, $screen_h, $os) {
        $quality = RenderLib::GetImageQuality($net);
        $scale = RenderLib::LargeImageScale($meta, $screen_w, $screen_h, $os);

        $url = sprintf( LARGE_CHANNEL_IMG_PATTERN,
                        urlencode($url_sign),
                        $scale[0], $scale[1], $quality);

        return array(
            "src" => $url,
            "width" => $scale[0],
            "height" => $scale[1],
            "pattern" => sprintf(LARGE_CHANNEL_IMG_PATTERN,
                                 urlencode($url_sign),
                                 "{w}", "{h}", $quality),
            );
    }

    public static function GetFilter($source_name) {
        $ret = array();
        $ret[] = array(
            "name" => "Outdated",
            "id" => "1",
        );

        $ret[] = array(
            "name" => "Boring topic",
            "id" => "2"
        );

        $ret[] = array(
            "name" => "Source: " . $source_name,
            "id" => "3"
        );

        $ret[] = array(
            "name" => "Poor writing",
            "id" => "4"
        );

        return $ret;
    }

    public static function VideoRender($video, $meta, $screen_w, $screen_h, $os) {
        $scale = RenderLib::LargeImageScale($meta, $screen_w, $screen_h, $os);
        return array(
            "youtube_id" => $video->youtube_video_id,
            "width" => $scale[0],
            "height" => $scale[1],
            "duration" => $video->duration,
            "description" => mb_substr($video->description, 0, VIDEO_DESCRIPTION_LIMIT, "UTF-8"),
            "display" => 0
            );
    }
}