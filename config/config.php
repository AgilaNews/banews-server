<?php

use Phalcon\Logger;

$settings = array (
    "appdirs" => array (
        "libraryDir" => LIBRARY_PATH,
        "controllerDir" => APP_PATH . "/app/controllers/",
        "modelDir" => APP_PATH . "/app/models",
        "modelDirPub" => LIBRARY_PATH . "/models",
        "policyDir" => LIBRARY_PATH . "/policies",
        "renderDir" => LIBRARY_PATH . "/renders/",
        "rankerDir" => LIBRARY_PATH . "/rankers/",
        "filterDir" => LIBRARY_PATH . "/filters/",
        "selectorDir" => LIBRARY_PATH . "/selectors/",
        "relatedRecSelectorDir" => LIBRARY_PATH . "/relatedRecSelectors/",
        "interveneDir" => LIBRARY_PATH . "/intervenes/",
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
        "host" => "10.8.91.237",
        "port" => "6087",
        'conn_timeout' => 30000,
        'call_timeout' => 300000,
        "product_key" => "agilanews",
    ),
    'abtest' => array(
        'host' => '10.8.91.237',
        'port' => '6097',
        'conn_timeout' => 30000,
        'call_timeout' => 50000,
        'product_key' => 'agilanews',
    ),
    'lrRanker' => array(
        'host' => '10.8.91.237',
        'port' => '6077',
        'conn_timeout' => 30000,
        'call_timeout' => 70000,
        'product_key' => 'agilanews',
    ),
    'bloomfilter' => array(
        'host' => "10.8.91.237",
        'port' => '6066',
        'conn_timeout' => 30000,
        'call_timeout' => 100000,
    ),
    'sphinx' => array(
        'host' => '10.8.69.141',
        'port' => '5034',
        'conn_timeout' => 300000,
        'call_timeout' => 1000000,
        'suite_name' => 'agila_timeline',
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
                       "feature" => array (
                                        "addr" => "tcp://127.0.0.1:7070",
                                        "category" => "samplefeature",
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
