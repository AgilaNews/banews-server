<?php
/**
 * 
 * @file    RenderLib.php
 * @authors Zhao Yulong (elysium.zyl@gmail.com)
 * @date    2016-12-04 17:45:14
 * @version $Id$
 */
use Phalcon\DI;
class RenderLib {
    /*
      时间线的新闻样式定义
     */
    // 大图模板号
    const NEWS_LIST_TPL_LARGE_IMG = 2;

    // 三图模板
    const NEWS_LIST_TPL_THREE_IMG = 3;

    // 单图模板
    const NEWS_LIST_TPL_TEXT_IMG = 4;

    // 纯文本模板
    const NEWS_LIST_TPL_RAW_TEXT = 5;

    // 纯图片模板
    const NEWS_LIST_TPL_RAW_IMG = 6;

    // 纯GIF模板
    const NEWS_LIST_TPL_GIF = 7;

    // 视频模板, 非视频频道
    const NEWS_LIST_TPL_VIDEO_BIG = 10;

    // youtube视频资源, 小图
    const NEWS_LIST_TPL_SMALL_YOUTUBE = 11;

    // youtube视频资源模板，大图, 一般用于视频频道
    const NEWS_LIST_TPL_IN_VIDEO_CHANNEL = 12;

    // 视频列表样式，小图
    const NEWS_LIST_TPL_VIDEO_SMALL = 13;

    // 专题样式
    const NEWS_LIST_TOPIC = 14;

    // 用户兴趣选择模板
    const NEWS_LIST_INTERESTS = 15;

    // -------- 1000以上的模板号一般用于特殊运营
    // NBA样式
    const NEWS_LIST_TPL_NBA = 1000;

    // banner样式，一般用于常置顶，在刷新之后不会停留在时间线
    const NEWS_LIST_TPL_BANNER = 1001;

    // -------- 5000以上用于广告
    // facebook中图广告模板
    const NEWS_LIST_TPL_AD_FB_MEDIUM = 5000;

    const DETAIL_AD_TPL_MEDIUM = 5001;

    /*
      推荐展位的模板定义
     */
    // 纯文本
    const NEWS_LIST_RECOMMEND_RAW_TEXT = self::NEWS_LIST_TPL_RAW_TEXT;

    // 单图
    const NEWS_LIST_RECOMMEND_IMAGE_TEXT = self::NEWS_LIST_TPL_TEXT_IMG;

    // 单视频
    const NEWS_LIST_RECOMMEND_SMALL_VIDEO = self::NEWS_LIST_TPL_IN_VIDEO_CHANNEL;

    /*
      详情页模板
     */
    // 纯新闻详情页模板
    const NEWS_DETAIL_RAW_NEWS = 2;

    // 视频详情页模板
    const NEWS_DETAIL_VIDEO_NEWS = 3;

    // GIF详情页模板
    const NEWS_DETAIL_GIF_NEWS = 4;

    // 图片详情页模板
    const NEWS_DETAIL_PHOTO_NEWS = 5;

    // 列表页左下角, tag 相关
    const MAX_HOT_TAG =  2;
    const HOT_LIKE_THRESHOLD = 20;

    // 时间线展位
    const PLACEMENT_TIMELINE = 1;

    // 推荐展位
    const PLACEMENT_RECOMMEND = 2;

    // 通知中心展位
    const PLACEMENT_NOTIFICATION_CENTER = 3;

    // 收藏展位
    const PLACEMENT_COLLECT = 4;
    
    public static function GetPublicData($news_model) {
        $ret = array(
            "title" => $news_model->title,
            "news_id" => $news_model->url_sign,
            "source" => $news_model->source_name,
            "source_url" => $news_model->source_url,
            "public_time" => $news_model->publish_time,
            "likedCount" => $news_model->liked,
            "channel_id" => $news_model->channel_id,
            "share_url" => sprintf(SHARE_TEMPLATE, urlencode($news_model->url_sign)),
            "imgs" => array(),
            "videos" => array(),
            );
        return $ret;
    }

    public static function FillCommentsCount(&$ret) {
        $keys = array();

        foreach ($ret as &$cell) {
            if (array_key_exists("news_id", $cell)) {
                $keys []= $cell["news_id"];
            }
        }
        $comment_counts = Comment::getCount($keys);

        foreach ($ret as &$cell) {
            if(array_key_exists("news_id", $cell) && 
               array_key_exists($cell["news_id"], $comment_counts)) {
                $cell["commentCount"] = $comment_counts[$cell["news_id"]];
            } else {
                $cell["commentCount"] = 0;
            }
        }
    }

    public static function FillTags(&$ret) {
        $hot_tags = 0;
        
        foreach ($ret as &$cell) {
            $cell["tag"] = "";
            
            if (array_key_exists("tpl", $cell) &&
                $cell["tpl"] == self::NEWS_LIST_TOPIC){
                $cell["tag"] = "Topics";
                continue;
            }

            if (array_key_exists("channel_id", $cell) &&
                self::isVideoChannel($cell["channel_id"])) {
                $cell["tag"] = "Video";
                continue;
            }
            
            if ($hot_tags < self::MAX_HOT_TAG &&
                array_key_exists("likedCount", $cell) &&
                $cell["likedCount"] >= self::HOT_LIKE_THRESHOLD) {
                $cell["tag"] = "Hot";
                $hot_tags++;
            }
        }
    }

    public static function FillTpl(&$ret, $placement_id, $type) {
        foreach ($ret as &$cell) {
            if (!array_key_exists("tpl", $cell)) {
                switch ($type) {
                case self::PLACEMENT_RECOMMEND:
                    $cell["tpl"] = self::getRecommendTpl($cell["channel_id"], $cell);
                    break;
                case self::PLACEMENT_TIMELINE:
                    $cell["tpl"] = self::getTimelineTpl($cell["channel_id"], $placement_id, $cell);
                    break;
                case self::PLACEMENT_COLLECT:
                    $cell["tpl"] = self::getCollectTpl($cell["channel_id"], $cell);
                    break;
                case self::PLACEMENT_NOTIFICATION_CENTER:
                    $cell["tpl"] = self::getNotificationTpl($cell);
                    break;
                }
            }
        }
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

    public static function ImageRender($net, $url_sign, $meta, $large, $play_sign = false, $size = 1) {
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

        if ($play_sign) {
            $cell["pattern"] .= "|v=".$size;
        }

        return $cell;
    }

    protected  static function LargeImageScale($meta, $screen_w, $screen_h, $os) {
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

    public static function LargeImageRender($pattern, $net, $url_sign, $meta, $screen_w, $screen_h, $os, $play_sign = false, $ensure_width=false) {
        $quality = RenderLib::GetImageQuality($net);
        $scale = RenderLib::LargeImageScale($meta, $screen_w, $screen_h, $os);

        if ($play_sign) {
            $pattern = $pattern . "|v=1";
        }
        
        $url = sprintf( $pattern,
                        urlencode($url_sign),
                        $scale[0], $scale[1], $quality);

        $w = "{w}";
        $h = "{h}";
        if ($ensure_width) {
            $h = "";
        }

        return array(
            "src" => $url,
            "width" => $scale[0],
            "height" => $scale[1],
            "pattern" => sprintf($pattern,
                                 urlencode($url_sign),
                                 $w, $h, $quality),
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

    
    private static function getCollectTpl($channel_id, $cell) {
        return self::getTimelineTpl($channel_id, null, $cell);
    }
    
    private static function getTimelineTpl($channel_id, $placement_id, $cell) {
        if (self::isGifChannel($channel_id)) {
            return self::NEWS_LIST_TPL_GIF;
        }
        if (self::isPhotoChannel($channel_id)) {
            return self::NEWS_LIST_TPL_RAW_IMG;
        }
        
        if (array_key_exists("videos", $cell) && $cell["videos"]) {
            if (self::isVideoChannel($channel_id) && self::isVideoChannel($placement_id)) {
                return self::NEWS_LIST_TPL_IN_VIDEO_CHANNEL;
            } else {
                return self::NEWS_LIST_TPL_VIDEO_BIG;
            }
        }

        if (array_key_exists("__large_image", $cell)) {
            unset($cell["__large_image"]);
            return self::NEWS_LIST_TPL_LARGE_IMG;
        }

        if (count($cell["imgs"]) == 0) {
            return self::NEWS_LIST_TPL_RAW_TEXT;
        } else if (count($cell["imgs"]) <= 2) {
            $cell["imgs"] = array_slice($cell["imgs"], 0, 1);
            return self::NEWS_LIST_TPL_TEXT_IMG;
        } else if (count($cell["imgs"]) >= 3) {
            $cell["imgs"] = array_slice($cell["imgs"], 0, 3);
            return self::NEWS_LIST_TPL_THREE_IMG;
        }

        return self::NEWS_LIST_TPL_RAW_TEXT;
    }

    
    private static function getRecommendTpl($channel_id, $cell) {
        if (array_key_exists("videos", $cell) && $cell["videos"]) {
            return self::NEWS_LIST_RECOMMEND_SMALL_VIDEO;
        }

        if (count($cell["imgs"]) >= 1) {
            $cell["imgs"] = array_slice($cell["imgs"], 0, 1);
            return self::NEWS_LIST_RECOMMEND_IMAGE_TEXT;
        }

        return self::NEWS_LIST_RECOMMEND_RAW_TEXT;
    }

    
    private static function getNotificationTpl($cell) {
        if (array_key_exists("news_id", $cell)) {
            $news = News::getBySign($cell["news_id"]);
            if ($news) {
                if (self::isVideoChannel($news->channel_id)) {
                    return self::NEWS_DETAIL_VIDEO_NEWS;
                }
                if (self::isGifChannel($news->channel_id)) {
                    return self::NEWS_DETAIL_GIF_NEWS;
                }
                if (self::isPhotoChannel($news->channel_id)) {
                    return self::NEWS_DETAIL_PHOTO_NEWS;
                }
            }
        }
        
        return self::NEWS_DETAIL_RAW_NEWS;
    }

    
    public static function isVideoChannel($channel_id) {
        return (int)($channel_id/10000) == 3;
    }
    
    public static function isGifChannel($channel_id) {
        return $channel_id == 10012;
    }

    public static function isPhotoChannel($channel_id) {
        return $channel_id == 10011;
    }
}
