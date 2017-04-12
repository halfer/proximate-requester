<?php

/**
 * Demo integration test
 */

namespace Proximate\Tests\Integration;

use Proximate\Tests\Integration\TestCase;
use Openbuildings\Spiderling\Driver_Simple;
use Proximate\Client;

class IntegrationTest extends TestCase
{
    const URL_BASE = 'http://127.0.0.1:8090';
    const URL_PROXY = 'http://127.0.0.1:8082';

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
     * @todo This can probably be moved to the parent class
     */
    public static function setUpBeforeClass()
    {
        $root = realpath(__DIR__ . '/../../..');
        $command = "php {$root}/test/integration/scripts/proxy.php >/dev/null &";

        $output = $return = null;
        exec($command, $output, $return);
        if ($return)
        {
            throw new \Exception(
                "Could not start the proxy server script"
            );
        }

        // The proxy needs some settling down time, maybe we could add a feature into
        // the Proximate\Client to do this better?
        sleep(2);
    }

    /**
     * Wipe the proxy server cache between tests
     */
    public function setUp()
    {
        // @todo Implement the cache clearing at /tmp/proximate-tests/cache
    }

    /**
     * Shuts down the proxy server
     *
     * @todo This can probably be moved to the parent class
     */
    public static function tearDownAfterClass()
    {
        $client = new Client(self::URL_PROXY);
        $client->fetch('SHUTDOWN');
    }
}
