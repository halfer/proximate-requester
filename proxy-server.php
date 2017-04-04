<?php

/**
 * Code to set up a simple HTTP proxy server
 */

// Enables pcntl signal catching
declare(ticks = 1);

require_once 'vendor/autoload.php';

use Socket\Raw\Factory as SocketFactory;

use League\Flysystem\Adapter\Local as LocalFileAdapter;
use League\Flysystem\Filesystem;
use Cache\Adapter\Filesystem\FilesystemCachePool;

use Proximate\CacheAdapter\Filesystem as FilesystemCacheAdapter;

use Monolog\Logger;
use Monolog\Handler\ErrorLogHandler;

// Here is the basis of the listening system
$factory = new SocketFactory();
$client = $factory->createServer('localhost:9001');

// This sets up the cache storage system
$filesystemAdapter = new LocalFileAdapter(__DIR__);
$filesystem = new Filesystem($filesystemAdapter);
$cachePool = new FilesystemCachePool($filesystem);

// Here is a dependency to perform additional ops on the cache
$cacheAdapter = new FilesystemCacheAdapter($filesystem);

// Here is the optional logger to inject
$logger = new Logger('stdout');
$logger->pushHandler(new ErrorLogHandler());

$proxier = new Proximate\Proxy($client, $cachePool, $cacheAdapter);
$proxier->
    checkSocketsAvailable()->
    addLogger($logger)->
    handleTerminationSignals()->
    listenLoop();
