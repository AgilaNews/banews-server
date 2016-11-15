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
use Elasticsearch\ClientBuilder;

$di = new FactoryDefault();

require(ROOT_PATH . "/library/pb/comment.php");
require(ROOT_PATH . "/library/pb/abtest.php");

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

$di->set('comment', function() use ($config) {
    $client = new iface\CommentServiceClient(sprintf("%s:%s", $config->comment->host, $config->comment->port), 
                                             [
                                                 'credentials' => Grpc\ChannelCredentials::createInsecure(),
                                                 'timeout' => $config->comment->call_timeout,
                                             ]);
    
    try {
        $client->waitForReady($config->comment->conn_timeout);
    } catch(\Exception $e) {
        return false;
    }
    
    return $client;
});

$di->set('abtest', function() use ($config) {
        $client = new iface\AbtestServiceClient(sprintf("%s:%s", $config->abtest->host, $config->abtest->port), 
                                                [
                                                'credentials' => Grpc\ChannelCredentials::createInsecure(),
                                                'timeout' => $config->abtest->call_timeout,
                                                ]);
        try {
            $client->waitForReady($config->abtest->conn_timeout);
        } catch (\Exception $e){
            return false;
        }

        if ($client) {
            return new Abservice($client);
        } else {
            return false;
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

$di->set('elasticsearch', function() use ($config) {
    $hosts = array($config->elasticsearch);
    $clientBuilder = ClientBuilder::create();
    $clientBuilder->setHosts($hosts);
    $esClient = $clientBuilder->build();
    if (!$esClient) {
        return null;
    }
    return $esClient;
});

$di->set("config", $config);
