<?php

use Phalcon\Mvc\View;
use Phalcon\DI\FactoryDefault;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\Url as UrlProvider;

$di = new FactoryDefault();

$di->set('dispatch', function () use ($di) {
    $em = new EventsManager;


    $em->attach("dispatch:beforeException", function ($event, $dispatcher, $exception) {
            $dispatcher->forward (
                                  "controller" => "index",
                                  "action" => "error",
                                  "params" => array($exception),
                                  )
                });
    // register plugins here
});

$di->set('db', function() use ($config) {
    $db_clz = 'Phalcon\Db\Adapter\Pdo\\' . $config->db->adapter;
    
    return new $db_clz($config->db->conf);
});

$di->set('view', function () use ($config) {
    $view = new View();
    return $view;
});
