<?php

/**
 * Simple proxy shutdown script
 */

$rootPath = realpath(__DIR__ . '/..');
require_once $rootPath . '/vendor/autoload.php';

$requester = new Proximate\Client('localhost:8081');

// @todo The url is necessary for now, as curl currently is trying to connect to a
// proxy target, however for a shutdown we want to connect to the proxy itself! Need
// a new shutdown() command in the client.
$ok = $requester->
    setUrl('http://localhost')->
    fetch('SHUTDOWN');
