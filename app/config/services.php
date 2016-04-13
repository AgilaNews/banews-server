<?php

use Phalcon\Mvc\View;
use Phalcon\DI\FactoryDefault;
use Phalcon\Mvc\Dispatcher as MvcDispatcher;
use Phalcon\Mvc\Url as UrlProvider;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Logger\Formatter\Line as LineFormatter;


$di = new FactoryDefault();

$di->set('dispatcher', function () {
        $em = new EventsManager();

        $em->attach("dispatch:beforeException", function ($event, $dispatcher, $exception) {
                $dispatcher->forward(
                                     array(
                                           "controller" => "index",
                                           "action" => "error",
                                           "params" => array($exception),
                                           )
                                     );
                return false;
            }
            );
        
        $dispatcher = new MvcDispatcher();
        $dispatcher->setEventsManager($em);
        return $dispatcher;
    });

$di->set('db', function() use ($config) {
    $db_clz = 'Phalcon\Db\Adapter\Pdo\\' . $config->db->adapter;
    
    return new $db_clz($config->db->conf);
});

$di->set('view', function () use ($config) {
    $view = new View();
    return $view;
});

$di->set('logger', function() use ($config) {
    $logger = new BanewsLogger($config->logger->banews->path,
                                     array(
                                           'mode' => 'w+',
                                           ));
    $logger->setLogLevel($config->logger->banews->level);
    $logger->setFormatter(new LineFormatter($config->logger->banews->format));

    return $logger;
});