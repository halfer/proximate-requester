<?php

/**
 * Code to set up a simple HTTP proxy server
 */

require_once 'vendor/autoload.php';
require_once 'src/Proximate/Proxier.php';

use Socket\Raw\Factory as SocketFactory;
use Monolog\Logger;
use Monolog\Handler\ErrorLogHandler;

$factory = new SocketFactory();
$client = $factory->createServer('localhost:9001');

$logger = new Logger('stdout');
$logger->pushHandler(new ErrorLogHandler());

$proxier = new Proximate\Proxier($client);
$proxier->
    checkSocketsAvailable()->
    addLogger($logger)->
    listenLoop();
