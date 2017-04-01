<?php

/**
 * Code to set up a simple HTTP proxy server
 */

require_once 'vendor/autoload.php';
require_once 'src/Proximate/Proxier.php';

use \Socket\Raw\Factory as SocketFactory;

$factory = new SocketFactory();
$client = $factory->createServer('localhost:9001');

$proxier = new Proximate\Proxier($client);
$proxier->
    checkSocketsAvailable()->
    listenLoop();
