<?php

use Phalcon\Logger;

define("LIBRARY_PATH", "/home/work/banews-server/library");
$settings = array (
    "appdirs" => array (
        "libraryDir" => LIBRARY_PATH,
        "controllerDir" => APP_PATH . "/app/controllers/",
        "modelDir" => APP_PATH . "/app/models",
        "policyDir" => LIBRARY_PATH . "/policies",
        "renderDir" => LIBRARY_PATH . "/renders/",
        "selectorDir" => LIBRARY_PATH . "/selectors/",
    ),
    'db_w' => array (
        'adapter' => 'Mysql',
        'conf' => array(
            'host' => '10.8.22.123',
            'username' => 'root',
            'password' => 'MhxzKhl-Happy',
            'dbname' => 'banews',
            "options" => array(
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
                ),
            ),
         ),
    'db_r' => array (
        'adapter' => 'Mysql',
        'conf' => array (
            'host' => '10.8.31.41',
            'username' => 'banews_r',
            'password' => 'MhxzKhlHappy',
            'dbname' => 'banews',
            "options" => array(
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
                ),
            ),
        ),
    'logger' => array (
                       'banews' => array (
                                          'path' => "/data/logs/banews-server/banews-server.log",
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
                                        "host" => "10.8.15.189",
                                        "port" => 6379,
                                        ),
                      ),
    'entries' => array (
                        "home" => NET_SCHEMA . "://" . SERVER_NAME . "/v%d",
                        "log" => NET_SCHEMA . "://" . LOG_SERVER_NAME . "/v%d",
                        "mon" => NET_SCHEMA . "://" .  MON_SERVER_NAME . "/v%d",
                        "h5" => "http://" . H5_SERVER_NAME . "/news?news_id=",
                        "referrer" => NET_SCHEMA . "://" . SERVER_NAME . "/referrer",

                        "homes" => "https://" . SERVER_NAME . "/v%d",
                        "logs" => "https://" . SERVER_NAME . "/v%d",
                        "mons" => "https://" . SERVER_NAME . "/v%d",
                        "referrers" => "https://" . SERVER_NAME . "/referrer",
                        ),

);
