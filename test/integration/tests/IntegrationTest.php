<?php

/**
 * Demo integration test
 */

namespace Proximate\Tests\Integration;

use Proximate\Tests\Integration\TestCase;
use Openbuildings\Spiderling\Driver_Simple;

class IntegrationTest extends TestCase
{
    const URL_BASE = 'http://127.0.0.1:8090';
    const URL_PROXY = 'http://127.0.0.1:8081';

    /**
     * @driver simple
     */
    public function testSomething()
    {
        $text = $this->
            visit(self::URL_BASE . '/test.html')->
            find('div')->
            text();
        $this->assertContains('Hello', $text);
    }

    public function driver_simple() : Driver_Simple
    {
        $driver = new Driver_Simple();
        $requestFactory = new HTTP();
        $requestFactory->setProxyAddress(self::URL_PROXY);
        $driver->request_factory($requestFactory);

        return $driver;
    }

    /**
     * Turns on the proxy server
     *
     * @todo Pass the proxy URL to this, so it is more customisable
     * @todo Pass in a custom file cache path
     * @todo This can probably be moved to the parent class
     */
    public function setUpBeforeClass()
    {
        $root = realpath(__DIR__ . '/../../..');
        $command = "php {$root}/console/proxy-server.php &";

        $output = $return = null;
        exec($command, $output, $return);
        if ($return)
        {
            throw new \Exception(
                "Could not start the proxy server script"
            );
        }
    }

    /**
     * Wipe the proxy server cache between tests
     */
    public function setUp()
    {
    }

    /**
     * Shuts down the proxy server
     *
     * @todo Send a KILL verb to the proxy server
     * @todo This can probably be moved to the parent class
     */
    public function tearDownAfterClass()
    {
        echo "Shut down the proxy server here\n";
    }
}
