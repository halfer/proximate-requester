<?php

/**
 * Proxy server script specifically for test purposes
 */

$rootPath = realpath(__DIR__ . '/../../..');
require_once $rootPath . '/vendor/autoload.php';

use Proximate\Proxy\FileProxy;

// Create a temp cache if required
$cachePath = '/tmp/proximate-tests';
@mkdir($cachePath);

try
{
    $proxier = new FileProxy('localhost:8082', $cachePath);
    // Init a proxy without a logger
    $proxier->
        initServer()->
        initFileCache()->
        initProxy()->
        getProxy()->
        listenLoop();
}
catch (\Proximate\Exception\Init $e)
{
    echo $e->getMessage() . "\n";
}
