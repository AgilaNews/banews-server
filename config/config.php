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
        "relatedRecSelectorDir" => LIBRARY_PATH . "/relatedRecSelectors/",
    ),
    'db_w' => array (
        'adapter' => 'Mysql',
        'conf' => array(
            'host' => '10.8.22.123',
            'username' => 'banews_w',
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
    'comment' => array(
        "host" => "10.8.23.37",
        "port" => "6087",
        "product_key" => "agilanews",
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
                        "home" => "http://" . SERVER_NAME . "/v%d",
                        "log" =>  "http://" . LOG_SERVER_NAME . "/v%d",
                        "mon" =>  "http://" .  MON_SERVER_NAME . "/v%d",
                        "h5" =>   "http://" . H5_SERVER_NAME . "/news?news_id=",
                        "referrer" => "http://" . SERVER_NAME . "/referrer",
                        ),
    "ufile" => array(
                        "public_key" => "UUztwD49TCzQ39diGb2T4a/0uYMwE6/PWII6fWwtuCiDQRQBfslLNg==",
                        "private_key" => "ef1716513ea5eb553737d08ce056e30ed9510d72",
                        "bucket" => "agilanews",
                        "proxy" => ".internal-hk-01.ufileos.com",
                        "suffix" => ".hk.ufileos.com",
                   ),
    'elasticsearch' => 'http://10.8.18.130:9200',
);
