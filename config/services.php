<?php

use Phalcon\Mvc\View;
use Phalcon\DI\FactoryDefault;
use Phalcon\Mvc\Dispatcher as MvcDispatcher;
use Phalcon\Mvc\Url as UrlProvider;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Logger\Formatter\Line as LineFormatter;
use Phalcon\Logger\Formatter\Json as JsonFormatter;
use Phalcon\Cache\Frontend\Json as JsonFront;
use Phalcon\Cache\Frontend\Data as DataFront;

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


$di->set('db_w', function() use ($config) {
    $db_clz = 'Phalcon\Db\Adapter\Pdo\\' . $config->db_w->adapter;
    
    return new $db_clz($config->db_w->conf->toArray());
    });

$di->set('db_r', function() use ($config) {
    $db_clz = 'Phalcon\Db\Adapter\Pdo\\' . $config->db_r->adapter;
    
    return new $db_clz($config->db_r->conf->toArray());
    });

$di->set('view', function () use ($config) {
    $view = new View();
    return $view;
});

    
$di->set('logger', function() use($config) {
    $logger = new BanewsLogger($config->logger->banews->path);
    $logger->setLogLevel($config->logger->banews->level);
    $logger->setFormatter(new LineFormatter($config->logger->banews->format));

    return $logger;
});

$di->set('eventlogger', function() use ($config) {
    try {
        $el = new EventLogger($config->logger->event->addr, $config->logger->event->category);
        return $el;
    } catch (\Exception $e) {
        return null;
    }
});


$di->set('cache', function() use ($config) {
    $cache = new Redis();
    $ret = $cache->connect($config->cache->redis->host, 
                           $config->cache->redis->port);
    if (!$ret) {
        return null;
    }

    return $cache;
});

$di->set("config", $config);
