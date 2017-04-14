<?php

/**
 * A device to start the web server when required
 */

namespace Proximate\Tests\Integration;

use halfer\SpiderlingUtils\NamespacedTestListener as BaseTestListener;
use halfer\SpiderlingUtils\Server;

class TestListener extends BaseTestListener
{
    const URL_SERVER_PORT_MIN = 20000;
    const URL_SERVER_PORT_MAX = 34999;

    protected static $webServerUrl;

	/**
	 * Required, return true if you recognise the test suite name or namespace
	 *
	 * Returning true turns on the internal web server
	 **/
	protected function switchOnBySuiteName($name)
	{
		return (strpos($name, 'Proximate\\Tests\\Integration\\') !== false);
	}

	/**
	 * Here's how to spin up a single server
	 */
	protected function setupServers()
	{
		$docRoot = realpath(__DIR__ . '/..') . '/web';
        self::$webServerUrl =
            'http://127.0.0.1:' .
            self::choosePort(self::URL_SERVER_PORT_MIN, self::URL_SERVER_PORT_MAX);
		$server = new Server($docRoot, self::$webServerUrl);

		// Wait for an alive response
        $integrationRoot = realpath(__DIR__ . '/..');
		$server->setRouterScriptPath($integrationRoot . '/scripts/router.php');
		$server->setCheckAliveUri('/server-check');

		$this->addServer($server);
	}

    /**
     * Cycles through a port choice depending on the current UNIX time
     *
     * @param integer $min
     * @param integer $max
     */
    protected static function choosePort($min, $max)
    {
        $range = $max - $min + 1;
        $mod = time() % $range;
        $port = $min + $mod;

        return $port;
    }

    public static function getWebServerUrl()
    {
        return self::$webServerUrl;
    }
}
