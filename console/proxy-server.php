<?php

/**
 * Code to set up a simple HTTP proxy server
 */

$rootPath = realpath(__DIR__ . '/..');
require_once $rootPath . '/vendor/autoload.php';

use Proximate\Proxy\FileProxy;

try
{
    $proxier = new FileProxy('localhost:8081', $rootPath . '/cache');
    $proxier->
        initSimpleSystem()->
        getProxy()->
        enableDebugHeaders()->
        listenLoop();
}
catch (\Proximate\Exception\Init $e)
{
    echo $e->getMessage() . "\n";
}
