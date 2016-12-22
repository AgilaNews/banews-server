<?php

use Phalcon\Logger;

define("LIBRARY_PATH", APP_PATH . "../library");

$settings = array (
    "appdirs" => array (
        "libraryDir" => LIBRARY_PATH,
        "controllerDir" => APP_PATH . "/app/controllers/",
        "modelDir" => APP_PATH . "/app/models/",
        "modelDirPub" => LIBRARY_PATH . "/models",
        "policyDir" => LIBRARY_PATH . "/policies/",
        "renderDir" => LIBRARY_PATH . "/renders/",
        "rankerDir" => LIBRARY_PATH . "/rankers/",
        "selectorDir" => LIBRARY_PATH . "/selectors/",
        "relatedRecSelectorDir" => LIBRARY_PATH . "/relatedRecSelectors/",
        "interveneDir" => LIBRARY_PATH . "/intervenes/",
    ),
    'db_w' => array (
        'adapter' => 'Mysql',
        'conf' => array(
#            'host' => '10.8.6.7',
            'host' => '127.0.0.1',
            'username' => 'banews_w',
            'password' => 'MhxzKhl-Happy!@#',
            'dbname' => 'banews',
            "options" => array(
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
                ),
            ),
            ),
    'db_r' => array (
        'adapter' => 'Mysql',
        'conf' => array(
#            'host' => '10.8.6.7',       
            'host' => '127.0.0.1',
            'username' => 'root',
            'password' => 'MhxzKhl-Happy!@#',
            'dbname' => 'banews',
            "options" => array(
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
                ),
            ),
            ),
    'comment' => array(
        'host' => '127.0.0.1',
        'port' => '6087',
        'conn_timeout' => 30,
        'call_timeout' => 100,
        'product_key' => 'agilanews',
    ),
    'abtest' => array(
        'host' => '127.0.0.1',
        'port' => '6097',
        'conn_timeout' => 30000,
        'call_timeout' => 100000,
        'product_key' => 'agilanews',
    ),
    'lrRanker' => array(
        'host' => '127.0.0.1',
        'port' => '6077',
        'conn_timeout' => 30000,
        'call_timeout' => 100000,
        'product_key' => 'agilanews',
    ),
    'bloomfilter' => array(
        'host' => '127.0.0.1',
        'port' => '6066',
        'conn_timeout' => 30000,
        'call_timeout' => 100000,
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
            #                            "host" => "10.8.14.136",
                                        "host" => "127.0.0.1",
                                        "port" => 6379,
                                        ),
                      ),
    'entries' => array (
                        "home" => SERVER_NAME . "/v%d",
                        "log" => "http://" . LOG_SERVER_NAME . "/v%d",
                        "mon" => "http://" .  MON_SERVER_NAME . "/v%d",
                        "h5" => "http://" . H5_SERVER_NAME . "/news?news_id=",
                        "referrer" => "http://" . SERVER_NAME . "/referrer",
                        ),
    "ufile" => array(
                        "public_key" => "UUztwD49TCzQ39diGb2T4a/0uYMwE6/PWII6fWwtuCiDQRQBfslLNg==",
                        "private_key" => "ef1716513ea5eb553737d08ce056e30ed9510d72",
                        "bucket" => "agilanewssandbox",
                        "proxy" => ".hk.ufileos.com",
                        "suffix" => ".hk.ufileos.com"
                                    ),
    'elasticsearch' => 'http://localhost:9200', 
);
