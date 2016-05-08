<?php

error_reporting(E_ALL);

define('APP_PATH', realpath('..') . '/');
define('SERVER_NAME', "api.agilanews.com");
define('LOG_SERVER_NAME', 'api.agilanews.com');
define('MON_SERVER_NAME', 'api.agilanews.com');

define('MIN_VERSION', "0.0.1"); //TODO change this to a configuration center
define('NEW_VERSION', "0.0.2");
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
    echo "Exception ", $e->getMessage();
}
