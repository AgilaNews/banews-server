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
            'host' => '10.8.22.123',
            'username' => 'root',
            'password' => 'MhxzKhl-Happy',
            'dbname' => 'banews-test'
                        )
                   ),
    'logger' => array (
                       'banews' => array (
                                          'path' => APP_PATH . "app/logs/banews.log",
                                          'level' => Logger::INFO,
                                          'format' => "[%date%][%type%]: %message%",
                                        ),
                       "event" => array(
                                        "addr" => "tcp://127.0.0.1:7069",
                                        "category" => "useraction",
                                        ),
                       ),
    'cache' => array (
                      "general_life_time" => 0,
                      "redis" => array (
                                        "host" => "127.0.0.1",
                                        "port" => 6379,
                                        ),
                      "keys" => array(
                                 "version" => "BA_version",
                                 "news" => "BA_news",
                                 "user" => "BA_user",
                                 ),
                      ),
    'entries' => array (
                        "home" => "http://" . SERVER_NAME . "/v%d",
                        "log" => "http://" . LOG_SERVER_NAME . "/v%d",
                        "mon" => "http://" .  MON_SERVER_NAME . "/v%d",
                        ),

);
