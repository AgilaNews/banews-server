<?php

error_reporting(E_ALL);
define('APP_PATH', realpath('..') . '/');
define('ROOT_PATH', "/home/work/banews-server/");
define("VENDOR_PATH", "/home/limeng/php-env/vendor");

use Phalcon\Config;
use Phalcon\Mvc\Application;

try {
    $env = getenv("BANEWS_ENV");
    
    if ($env == "rd") {
        define ("SERVER_HOST", "agilanews.com");
        require ROOT_PATH . "config/defines.php";
        require ROOT_PATH . "config/config.php.rd";
        define('BA_DEBUG', true);
    } else if ($env == "sandbox") {
        define ("SERVER_HOST", "agilanews.info");
        require ROOT_PATH . "config/defines.php";
        require ROOT_PATH . "config/config.php.sandbox";
        define('BA_DEBUG', true);
    } else {
        define ("SERVER_HOST", "agilanews.today");
        require ROOT_PATH . "config/defines.php";
        require ROOT_PATH . "config/config.php";
        define('BA_DEBUG', false);
    }
    $config = new Config($settings);

    require VENDOR_PATH . "autoload.php"; 
    require ROOT_PATH . "config/loader.php";
    require ROOT_PATH . "config/services.php";
    $app = new Application($di);

    echo $app->handle()->getContent();
} catch (\Exception $e) {
    error_log(sprintf("Exception [%s:%s]: %s", $e->getFile(), $e->getLine(),  
                     $e->getTraceAsString()));

    $response = new \Phalcon\Http\Response();
    $response->setStatusCode(500);
    $response->setContent(json_encode(array("code" => ERR_INTERNAL_DB, "message" => "internal error")));
    $response->send();
}
