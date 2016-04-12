<?php

error_reporting(E_ALL);

define('APP_PATH', realpath('..') . '/');

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
