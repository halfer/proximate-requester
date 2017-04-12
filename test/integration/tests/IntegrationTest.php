<?php

/**
 * Demo integration test
 */

namespace Proximate\Tests\Integration;

use Proximate\Tests\Integration\TestCase;

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
}
