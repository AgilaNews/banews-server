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
define('CACHE_COLLECT_PREFIX', "BS_COLLECT_");
define('CACHE_COLLECT_TTL', 300);

define('CACHE_SENT_QUEUE_PREFIX', "BA_UN_FIFO_");

define('ANDROID_VERSION_CODE', 5);
define('MIN_VERSION', "v1.0.0"); //TODO change this to a configuration center
define('NEW_VERSION', "v1.0.1");

//TODO if user grows, set this value lesser, this will absolutely consume more memory
define('CACHE_SENT_MASK_MAX', 2000); 
define('CACHE_SENT_TTL', 4 * 3600); 

define('SHARE_TEMPLATE', "http://share.agilanews.today/news?id=%s");
define('UPDATE_URL', "https://play.google.com/store/apps/details?id=com.upeninsula.banews");


