<?php

use Phalcon\Logger;

$settings = array (
    "appdirs" => array (
        "libraryDir" => APP_PATH . "/app/library/",
        "validatorDir" => APP_PATH . "/app/validators",
        "controllerDir" => APP_PATH . "/app/controllers/",
        "modelDir" => APP_PATH . "/app/models",
    ),

    'db' => array (
        'adapter' => 'Mysql',
        'conf' => array(
            'host' => 'localhost',
            'username' => 'root',
            'password' => 'MhxzKhl-Happy!@#',
            'dbname' => 'banews'
                        )
                   ),
    'logger' => array (
                       'banews' => array (
                                          'path' => APP_PATH . "app/logs/banews.log",
                                          'level' => Logger::INFO,
                                          'format' => "[%date%][%type%]: %message%",
                                        ),
                       ),
    'cache' => array (
                      "life_time" => 0,
                      "redis" => array (
                                        "host" => "127.0.0.1",
                                        "port" => 6379,
                                        ),
                      "keys" => array(
                                 "version" => "BA_version",
                                 ),
                      ),
    'entries' => array (
                        "home" => SERVER_NAME . "/v%d",
                        "log" => LOG_SERVER_NAME . "/v%d",
                        "mon" => MON_SERVER_NAME . "/v%d",
                        ),

);
