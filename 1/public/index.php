<?php

error_reporting(E_ALL);

define('APP_PATH', realpath('..') . '/');
define('SERVER_NAME', "api.agilanews.com");

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

define("MAX_COMMENT_SIZE", 300);
define("MAX_COMMENT_COUNT", 50);
define('CACHE_USER_PREFIX', "USER_");
define('CACHE_USER_TTL', 86400);
define('CACHE_NEWS_PREFIX', "NEWS_");
define('CACHE_NEWS_TTL', 14400);
define('CACHE_COMMENTS_TTL', 600);
define('CACHE_COMMENTS_PREFIX', "COMMENTS_");

define('SHARE_TEMPLATE', "http://m.agilanews.com/news?id=%s");

use Phalcon\Config;
use Phalcon\Mvc\Application;

try {
    $env = getenv("BANEWS_ENV");
    
    if ($env == "rd") {
        require APP_PATH . "app/config/config.php.rd";
        define('BA_DEBUG', true);
    } else {
        require APP_PATH . "app/config/config.php";
    }
    $config = new Config($settings);

    $settings["appdirs"] = array (
        "libraryDir" => APP_PATH . "/app/library/",
        "validatorDir" => APP_PATH . "/app/validators",
        "controllerDir" => APP_PATH . "/app/controllers/",
        "modelDir" => APP_PATH . "/app/models",
    );

    require APP_PATH . "app/config/loader.php";

    require APP_PATH . "app/config/services.php";
    $app = new Application($di);

    echo $app->handle()->getContent();
} catch (\Exception $e) {
     echo "Exception: ", $e->getMessage(); // TODO
}
