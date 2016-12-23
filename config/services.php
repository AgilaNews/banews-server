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
require(ROOT_PATH . "/library/pb/requests.php");
require(ROOT_PATH . "/library/pb/abtest.php");
require(ROOT_PATH . "/library/pb/classify.php");
require(ROOT_PATH . "/library/pb/bloomfilter.php");
require(ROOT_PATH . "/library/pb/sphinx.php");

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
    }, true);

$di->set('view', function () use ($config) {
            $view = new View();
            return $view;
    }, true);

$di->set('db_w', function() use ($config) {
    $db_clz = 'Phalcon\Db\Adapter\Pdo\\' . $config->db_w->adapter;
    
    return new $db_clz($config->db_w->conf->toArray());
    }, true);

$di->set('db_r', function() use ($config) {
    $db_clz = 'Phalcon\Db\Adapter\Pdo\\' . $config->db_r->adapter;
    
    return new $db_clz($config->db_r->conf->toArray());
    }, true);
    
$di->set('logger', function() use($config) {
    $logger = new BanewsLogger($config->logger->banews->path);
    $logger->setLogLevel($config->logger->banews->level);
    $logger->setFormatter(new LineFormatter($config->logger->banews->format));

    return $logger;
    }, true);

$di->set('eventlogger', function() use ($config) {
    try {
        $el = new EventLogger($config->logger->event->addr, $config->logger->event->category);
        return $el;
    } catch (\Exception $e) {
        return null;
    }
    }, true);

$di->set('comment', function() use ($config) {
    $client = new iface\CommentServiceClient(sprintf("%s:%s", $config->comment->host, $config->comment->port), 
                                             [
                                                 'credentials' => Grpc\ChannelCredentials::createInsecure(),
                                                 'timeout' => $config->comment->conn_timeout,
                                             ]);
    
    try {
        $client->waitForReady($config->comment->conn_timeout);
    } catch(\Exception $e) {
        return false;
    }
    
    return $client;
    }, true);

$di->set('sphinx', function() use ($config) {
        $client = new iface\SphinxServiceClient(sprintf("%s:%s", $config->sphinx->host, $config->sphinx->port), 
                                                [
                                                'credentials' => Grpc\ChannelCredentials::createInsecure(),
                                                'timeout' => $config->sphinx->conn_timeout,
                                                ]);
        try {
            $client->waitForReady($config->sphinx->conn_timeout);
        } catch (\Exception $e){
            return false;
        }

        if ($client) {
            return new SphinxService($client);
        } else {
            return false;
        }
    }, true);

$di->set('abtest', function() use ($config) {
        $client = new iface\AbtestServiceClient(sprintf("%s:%s", $config->abtest->host, $config->abtest->port), 
                                                [
                                                'credentials' => Grpc\ChannelCredentials::createInsecure(),
                                                'timeout' => $config->abtest->conn_timeout,
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
    }, true);

$di->set('lrRanker', function() use ($config) {
    $client = new iface\ClassificationServiceClient(sprintf("%s:%s", 
        $config->lrRanker->host, $config->lrRanker->port), 
        ['credentials' => Grpc\ChannelCredentials::createInsecure(),
         'timeout' => $config->lrRanker->conn_timeout,]);
    try {
        $client->waitForReady($config->lrRanker->conn_timeout);
    } catch(\Exception $e) {
        return false;
    }
    return $client;
}, true);

$di->set("bloomfilter", function() use($config) {
        $client = new bloomiface\BloomFilterServiceClient(sprintf("%s:%s",
                                                             $config->bloomfilter->host, $config->bloomfilter->port),
                                                     ['credentials' => Grpc\ChannelCredentials::createInsecure(),
                                                      'timeout' => $config->bloomfilter->conn_timeout,]);
        try {
            $client->waitForReady($config->bloomfilter->conn_timeout);
        } catch(\Exception $e) {
            return false;
        }
        
        return new BloomFilterService($client);
    }, true);

$di->set('cache', function() use ($config) {
    $cache = new Redis();
    $ret = $cache->connect($config->cache->redis->host, 
                           $config->cache->redis->port);
    if (!$ret) {
        return null;
    }

    return $cache;
    }, true);

$di->set('elasticsearch', function() use ($config) {
    $hosts = array($config->elasticsearch);
    $clientBuilder = ClientBuilder::create();
    $clientBuilder->setHosts($hosts);
    $esClient = $clientBuilder->build();
    if (!$esClient) {
        return null;
    }
    return $esClient;
    }, true);

$di->set("config", $config);
