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

define('CACHE_SENT_QUEUE_PREFIX', "BA_UN_FIFO_");

define('ANDROID_VERSION_CODE', 8);
define('MIN_VERSION', "v1.0.1"); //TODO change this to a configuration center
define('NEW_VERSION', "v1.0.3");

//TODO if user grows, set this value lesser, this will absolutely consume more memory
define('CACHE_SENT_MASK_MAX', 1000); 
define('CACHE_SENT_TTL', 24 * 3600); 
define('IMG_CHANNEL_CACHE_SENT_MASK_MAX', 2000);
define('IMG_CHANNEL_CACHE_SENT_TTL', 72 * 3600);

define('UPDATE_URL', "https://play.google.com/store/apps/details?id=com.upeninsula.banews");

define('SHARE_TEMPLATE', "http://share." . SERVER_HOST . "/news?id=%s&from={from}");
define('SERVER_NAME', "api." . SERVER_HOST);
define('LOG_SERVER_NAME', 'log.' . SERVER_HOST);
define('MON_SERVER_NAME', 'mon.' . SERVER_HOST);
define('H5_SERVER_NAME', "m." . SERVER_HOST);
define('IMAGE_SERVER_NAME', "img." . SERVER_HOST);
define('IMAGE_PREFIX', "http://" . 
                       IMAGE_SERVER_NAME . 
                      '/image');


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

define('GIF_COVER_PATTERN', 
        IMAGE_PREFIX . "/%s_cover.jpg");
