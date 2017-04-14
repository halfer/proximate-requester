<?php

/**
 * A device to start the web server when required
 */

namespace Proximate\Tests\Integration;

use halfer\SpiderlingUtils\NamespacedTestListener as BaseTestListener;
use halfer\SpiderlingUtils\Server;

class TestListener extends BaseTestListener
{
    use PortChooser;

    const URL_SERVER_BASE = 'http://127.0.0.1';
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
        $serverPort = self::choosePort(
            self::URL_SERVER_PORT_MIN,
            self::URL_SERVER_PORT_MAX
        );
        self::$webServerUrl = self::URL_SERVER_BASE . ':' . $serverPort;
		$server = new Server($docRoot, self::$webServerUrl);

		// Wait for an alive response
        $integrationRoot = realpath(__DIR__ . '/..');
		$server->setRouterScriptPath($integrationRoot . '/scripts/router.php');
		$server->setCheckAliveUri('/server-check');

		$this->addServer($server);
	}

    public static function getWebServerUrl()
    {
        return self::$webServerUrl;
    }
}
