<?php

error_reporting(E_ALL);

define('APP_PATH', realpath('..') . '/');
define('ERR_KEY', 40001);
define('ERR_CLIENT_VERSION_NOT_FOUND', 40011);
define('ERR_INTERNAL_DB', 50002);

define('ANDROID_VERSION_CODE', 5);
define('MIN_VERSION', "v1.0.0"); //TODO change this to a configuration center
define('NEW_VERSION', "v1.0.1");
define('UPDATE_URL', "https://play.google.com/store/apps/details?id=com.upeninsula.banews");

use Phalcon\Config;
use Phalcon\Mvc\Application;

try {
    $env = getenv("BANEWS_ENV");

    if ($env == "rd") {
        define('SERVER_NAME', "api.agilanews.today");
        define('LOG_SERVER_NAME', 'log.agilanews.today');
        define('MON_SERVER_NAME', 'mon.agilanews.today');
        define('H5_SERVER_NAME', "m.agilanews.today");
        define('BA_DEBUG', true);

        require APP_PATH . "app/config/config.php.rd";
    } if ($env == "sandbox") {
        define('SERVER_NAME', "api.agilanews.info");
        define('LOG_SERVER_NAME', 'log.agilanews.info');
        define('MON_SERVER_NAME', 'mon.agilanews.info');
        define('H5_SERVER_NAME', "m.agilanews.info");

        require APP_PATH . "app/config/config.php.sandbox";
        define('BA_DEBUG', true);
    } else {
        define('SERVER_NAME', "api.agilanews.today");
        define('LOG_SERVER_NAME', 'log.agilanews.today');
        define('MON_SERVER_NAME', 'mon.agilanews.today');
        define('H5_SERVER_NAME', "m.agilanews.today");

        require APP_PATH . "app/config/config.php";
        define('BA_DEBUG', false);
    }
    $config = new Config($settings);
    require APP_PATH . "app/config/loader.php";
    require APP_PATH . "app/config/services.php";
    $app = new Application($di);

    echo $app->handle()->getContent();
} catch (\Exception $e) {
    if ($env == "rd") {
        echo "Exception ", $e->getMessage();
    } else {
        echo json_encode(array("error"=>ERR_INTERNAL_DB, "message"=>"internal error"));
    }
}
