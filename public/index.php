<?php

error_reporting(E_ALL);

define('APP_PATH', realpath('..') . '/');
define('SERVER_NAME', "110.96.191.65:9080");
define('LOG_SERVER_NAME', '110.96.191.65:9080');
define('MON_SERVER_NAME', '110.96.191.65:9080');
define('MIN_VERSION', "0.0.1"); //TODO change this to a configuration center
define('NEW_VERSION', "0.0.2");
define('UPDATE_URL', "http://demoupdate.googleplay.com");
define('BA_DEBUG', true);

define('ERR_KEY_ERR', 40001);
define('ERR_BODY_ERR', 40002);
define('ERR_CLIENT_VERSION_NOT_FOUND', 40011);
define('ERR_INVALID_METHOD', 40501);
define('ERR_INTERNAL_DB', 50002);

use Phalcon\Mvc\Application;
use phalcon\Config;

//try {
    require APP_PATH . "app/config/config.php";
    $config = new Config($settings);

    require APP_PATH . "app/config/loader.php";
    require APP_PATH . "app/config/services.php";
    $app = new Application($di);

    echo $app->handle()->getContent();

//} catch (\Exception $e) {
 //    echo "Exception: ", $e->getMessage();
//}
