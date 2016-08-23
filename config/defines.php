<?php
define('ERR_KEY', 40001);
define('ERR_BODY', 40002);
define('ERR_COMMENT_TOO_LONG', 40004);
define('ERR_FB_TOO_LONG', 40005);
define('ERR_CLIENT_VERSION_NOT_FOUND', 40011);
define('ERR_NOT_AUTH', 40101);
define('ERR_USER_NON_EXISTS', 40102);
define('ERR_NEWS_NON_EXISTS', 40103);
define('ERR_DEVICE_NON_EXISTS', 40104);

define('ERR_INVALID_METHOD', 40501);
define('CHANNELS_CACHE_KEY', "BS_CHANNELS_");
define('CHANNELS_CACHE_TTL', 7200);

define('ERR_COLLECT_CONFLICT', 40901);
define('ERR_COMMENT_TOO_MUCH', 40902);
define('ERR_INTERNAL_DB', 50002);

define("MAX_COMMENT_SIZE", 1024);
define("MAX_COMMENT_COUNT", 50);
define('CACHE_USER_PREFIX', "BS_USER_");
define('CACHE_USER_TTL', 86400);
define('CACHE_NEWS_PREFIX', "BS_NEWS_");
define('CACHE_NEWS_TTL', 14400);
define('CACHE_COMMENTS_TTL', 600);
define('CACHE_COMMENTS_PREFIX', "BS_COMMENTS_");
define('CACHE_IMAGES_PREFIX', "BS_IMAGES_");
define('CACHE_IMAGES_TTL', 14400);
define('CACHE_GIFS_PREFIX', "BS_GIFS_");
define('CACHE_GIFS_TTL', 14400);
define('CACHE_VERSION_PREFIX', "BS_VERSION_");
define('CACHE_VERSION_TTL', 14400);
define('CACHE_COLLECT_PREFIX', "BS_COLLECT_");
define('CACHE_COLLECT_TTL', 300);
define("CACHE_CHANNELS_V2_KEY", "BS_CHANNELS_V2_%s");
define('DEVICEMAP_TOKEN_KEY', "PUSH_TOKEN_");
define('DEVICEMAP_DEVICE_KEY', "PUSH_DEVICE_ID_");

define('CACHE_SENT_QUEUE_PREFIX', "BA_UN_FIFO_");
define('BACKUP_CHANNEL_CURSOR_KEY', 'BA_CH_CURSOR_KEY');
define('CHANNEL_USER_CURSOR_PREFIX', 'CH_DEVICE_CURSOR_');
define('BACKUP_CHANNEL_LIST_PREFIX', 'BA_CH_LIST_');

define('BUILD_MAIN', 1);
define('BUILD_ACCESSORY', 2);
define('V2_BASE_VERSION', "1.1.5");

define('ANDROID_VERSION_CODE', 18);
define('MIN_VERSION', "v1.0.1"); //TODO change this to a configuration center
define('NEW_VERSION', "v1.1.7");
define('UPDATE_URL', "https://play.google.com/store/apps/details?id=com.upeninsula.banews");

define('ANDROID_VERSION_CODE_2', 1);
define('MIN_VERSION_2', 'v1.0.1');
define('NEW_VERSION_2', 'v1.1.7');
define('UPDATE_URL_2', "https://play.google.com/store/apps/details?id=com.upeninsula.banews");

//TODO if user grows, set this value lesser, this will absolutely consume more memory
define('CACHE_SENT_MASK_MAX', 1000); 
define('CACHE_SENT_TTL', 24 * 3600); 
define('NET_SCHEMA', "http");


define('SHARE_TEMPLATE', "http://share." . SERVER_HOST . "/news?id=%s&from={from}");
define('SERVER_NAME', "api." . SERVER_HOST);
define('LOG_SERVER_NAME', 'log.' . SERVER_HOST);
define('MON_SERVER_NAME', 'mon.' . SERVER_HOST);
define('H5_SERVER_NAME', "m." . SERVER_HOST);
//define('VIDEO_SERVER_NAME', "video." . SERVER_HOST);
define('VIDEO_SERVER_NAME', "v1." . SERVER_HOST);
define('GIF_CHANNEL_PATTERN', "http://" . VIDEO_SERVER_NAME . "/video/%s.mp4");
//define('IMAGE_SERVER_NAME', "img." . SERVER_HOST);
define('IMAGE_SERVER_NAME', "s1." . SERVER_HOST);
define('IMAGE_PREFIX', "http://" . 
                       IMAGE_SERVER_NAME . 
                      '/image');

define('MAX_FB_SIZE', 1024);

define('CLIENT_CTIMEOUT', 5);
define('CLIENT_RTIMEOUT', 5);
define('CLIENT_WTIMEOUT', 5);

define("IMAGE_CHANNEL_HIGH_QUALITY", 50);
define("IMAGE_CHANNEL_LOW_QUALITY", 15);
define("IMAGE_CHANNEL_NORMAL_QUALITY", 30);
define("IMAGE_CHANNEL_IMG_PATTERN", IMAGE_PREFIX . 
       "/%s.jpg?p=s=%dX_w|c=%dX%d@0x0|q=%d");

define("DETAIL_IMAGE_QUALITY", 50);
define("DETAIL_IMAGE_PATTERN", IMAGE_PREFIX . "/%s.jpg?p=s=%dX_w|q=" . DETAIL_IMAGE_QUALITY);

define('BASE_CHANNEL_IMG_QUALITY', 45);
define("BASE_CHANNEL_IMG_PATTERN",
        IMAGE_PREFIX . 
        "/%s.jpg?p=t=%sx%s|q=" . BASE_CHANNEL_IMG_QUALITY);

define('LARGE_CHANNEL_IMG_QUALITY', 60);
define("LARGE_CHANNEL_IMG_PATTERN",
        IMAGE_PREFIX . 
        "/%s.jpg?p=t=%sx%s|q=" . LARGE_CHANNEL_IMG_QUALITY);


define('GIF_COVER_PATTERN', 
        IMAGE_PREFIX . "/%s_cover.jpg");
