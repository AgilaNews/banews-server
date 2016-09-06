<?php
define('BUILD_MAIN', 1);
define('BUILD_ACCESSORY', 2);

//used for publish
define('NOT_PUBLISHED', 0);
define('ANDROID_PUBLISHED', 1);
define('IOS_PUBLISHED', 2);
define('BOTH_PUBLISHED', ANDROID_PUBLISHED | IOS_PUBLISHED);

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

define('ERR_COLLECT_CONFLICT', 40901);
define('ERR_COMMENT_TOO_MUCH', 40902);
define('ERR_INTERNAL_DB', 50002);

define("MAX_COMMENT_SIZE", 1024);
define("MAX_COMMENT_COUNT", 50);

//used for redis
define('CACHE_CHANNELS_KEY', "BS_CHANNELS_");
define('CACHE_CHANNELS_TTL', 7200);
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
define('CACHE_VERSION_PREFIX', "BS_VERSIONS");
define('CACHE_VERSION_TTL', 1800);
define('CACHE_COLLECT_PREFIX', "BS_COLLECT_");
define('CACHE_COLLECT_TTL', 300);
define('CACHE_NEWS_RECOMMEND_PREFIX', "BS_NEWS_RECOMMEND_");
define('CACHE_NEWS_RECOMMEND_TTL', 3600);
define("CACHE_CHANNELS_V2_KEY", "BS_CHANNELS_V2_%s");
define('CACHE_COMMENT_FREQ_PREFIX', "BS_COMMENT_FREQ_");
define('CACHE_COMMENT_FREQ_TTL', 5);

//used for redis device map
define('DEVICEMAP_TOKEN_KEY', "PUSH_TOKEN_");
define('DEVICEMAP_DEVICE_KEY', "PUSH_DEVICE_ID_");

//used for news dispatch
define('CACHE_SENT_QUEUE_PREFIX', "BA_UN_FIFO_");
define('CACHE_CLICK_QUEUE_PREFIX', "BA_UC_FIFO_");
define('BACKUP_CHANNEL_CURSOR_KEY', 'BA_CH_CURSOR_KEY');
define('CHANNEL_USER_CURSOR_PREFIX', 'CH_DEVICE_CURSOR_');
define('BACKUP_CHANNEL_LIST_PREFIX', 'BA_CH_LIST_');

//TODO if user grows, set this value lesser, this will absolutely consume more memory, we will consider bloomfilter to solve this problem
define('CACHE_SENT_MASK_MAX', 1000); 
define('CACHE_SENT_TTL', 24 * 3600); 
define('CACHE_CLICK_MASK_MAX', 100);
define('CACHE_CLICK_TTL', 48 * 3600);
define('NET_SCHEMA', "http");

define('SHARE_TEMPLATE', "http://share." . SERVER_HOST . "/news?id=%s&from={from}");
define('SERVER_NAME', "api." . SERVER_HOST);
define('LOG_SERVER_NAME', 'log.' . SERVER_HOST);
define('MON_SERVER_NAME', 'mon.' . SERVER_HOST);
define('H5_SERVER_NAME', "m." . SERVER_HOST);

define('VIDEO_SERVER_NAME', "v1." . SERVER_HOST);
define('GIF_CHANNEL_PATTERN', "http://" . VIDEO_SERVER_NAME . "/video/%s.mp4");
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
