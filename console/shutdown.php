<?php

/**
 * Simple proxy shutdown script
 */

$rootPath = realpath(__DIR__ . '/..');
require_once $rootPath . '/vendor/autoload.php';

$requester = new Proximate\Client('localhost:8081');
$requester->fetch('SHUTDOWN');
