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
        $driver->request_factory(new HTTP());

        return $driver;
    }
}
