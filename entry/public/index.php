<?php

error_reporting(E_ALL);

define('APP_PATH', realpath('..') . '/');
define('SERVER_NAME', "api.agilanews.com");
define('ERR_KEY', 40001);
define('ERR_CLIENT_VERSION_NOT_FOUND', 40011);
define('ERR_INTERNAL_DB', 50002);
define('LOG_SERVER_NAME', 'log.agilanews.com');
define('MON_SERVER_NAME', 'mon.agilanews.com');
define('H5_SERVER_NAME', "m.agilanews.com");

define('ANDROID_VERSION_CODE', 2);
define('MIN_VERSION', "1.0.0"); //TODO change this to a configuration center
define('NEW_VERSION', "1.0.0");
define('UPDATE_URL', "http://demoupdate.googleplay.com");

use Phalcon\Config;
use Phalcon\Mvc\Application;

try {
    $env = getenv("BANEWS_ENV");

    if ($env == "rd") {
        require APP_PATH . "app/config/config.php.rd";
    } else {
        require APP_PATH . "app/config/config.php";
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
