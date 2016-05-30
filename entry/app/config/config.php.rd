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
                                          'path' => "/home/work/logs/banews.log",
                                          'level' => Logger::INFO,
                                          'format' => "[%date%][%type%]: %message%",
                                        ),
                       "event" => array(
                                        "addr" => "tcp://127.0.0.1:7070",
                                        "category" => "useraction",
                                        ),
                       ),
    'cache' => array (
                      "redis" => array (
                                        "host" => "127.0.0.1",
                                        "port" => 6379,
                                        ),
                      ),
    'entries' => array (
                        "home" => "http://" . SERVER_NAME . "/v%d",
                        "log" => "http://" . LOG_SERVER_NAME . "/v%d",
                        "mon" => "http://" .  MON_SERVER_NAME . "/v%d",
                        "h5" => "http://" . H5_SERVER_NAME . "/news?news_id=",
                        ),

);
