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
}
