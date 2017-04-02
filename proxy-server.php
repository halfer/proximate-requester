<?php

/**
 * Code to set up a simple HTTP proxy server
 */

require_once 'vendor/autoload.php';
require_once 'src/Proximate/Proxy.php';

use Socket\Raw\Factory as SocketFactory;
use League\Flysystem\Adapter\Local as LocalFileAdapter;
use League\Flysystem\Filesystem;
use Cache\Adapter\Filesystem\FilesystemCachePool;
use Monolog\Logger;
use Monolog\Handler\ErrorLogHandler;

$factory = new SocketFactory();
$client = $factory->createServer('localhost:9001');

$filesystemAdapter = new LocalFileAdapter(__DIR__ . '/cache');
$filesystem = new Filesystem($filesystemAdapter);
$cachePool = new FilesystemCachePool($filesystem);

$logger = new Logger('stdout');
$logger->pushHandler(new ErrorLogHandler());

$proxier = new Proximate\Proxy($client, $cachePool);
$proxier->
    checkSocketsAvailable()->
    addLogger($logger)->
    listenLoop();
