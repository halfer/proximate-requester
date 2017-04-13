<?php

/**
 * Initialises app ready for testing
 */

$rootPath = realpath(__DIR__ . '/..');
require_once $rootPath . '/vendor/autoload.php';

// Load classes for functional testing
$classPath = $rootPath . '/test/integration/classes';
require_once $classPath . '/TestCase.php';
require_once $classPath . '/TestListener.php';
require_once $classPath . '/HTTP.php';
require_once $classPath . '/ProxyTesting.php';
