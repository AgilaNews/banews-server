<?php

use Phalcon\Logger;

$settings = array (
    "appdirs" => array (
        "libraryDir" => APP_PATH . "/app/library/",
        "validatorDir" => APP_PATH . "/app/validators",
        "controllerDir" => APP_PATH . "/app/controllers/",
        "modelDir" => APP_PATH . "/app/models",
        "policyDir" => APP_PATH . "/app/policies",
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
                       "event" => array(
                                        "addr" => "tcp://127.0.0.1:7069",
                                        "category" => "useraction",
                                        ),
                       ),
    'cache' => array (
                      "general_life_time" => 0,
		              "user_life_time" => 86400,
		              "news_life_time" => 3600 * 4,
                      "comments_life_time" => 600,
                      "redis" => array (
                                        "host" => "127.0.0.1",
                                        "port" => 6379,
                                        ),
                      "keys" => array(
                                 "version" => "BA_version",
                                 "news" => "BA_news_",
                                 "user" => "BA_user_",
                                 "comments" => "BA_comments",
                                 "img" => "BA_images_",
                                 ),
                      ),
    'entries' => array (
                        "home" => SERVER_NAME . "/v%d",
                        ),

);
