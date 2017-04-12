<?php

/**
 * A device to start the web server when required
 */

namespace Proximate\Tests\Integration;

use halfer\SpiderlingUtils\NamespacedTestListener as BaseTestListener;
use halfer\SpiderlingUtils\Server;

class TestListener extends BaseTestListener
{
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
		$server = new Server($docRoot);

		// Wait for an alive response
        $integrationRoot = realpath(__DIR__ . '/..');
		$server->setRouterScriptPath($integrationRoot . '/scripts/router.php');
		$server->setCheckAliveUri('/server-check');

		$this->addServer($server);
	}
}
