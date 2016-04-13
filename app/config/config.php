<?php

use Phalcon\Logger;

$settings = array (
    "appdirs" => array (
        "libraryDir" => APP_PATH . "/app/library/",
        "validatorDir" => APP_PATH . "/app/validators",
        "controllerDir" => APP_PATH . "/app/controllers/"
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
    

);
