<?php

/**
 * Demo integration test
 */

namespace Proximate\Tests\Integration;

use Proximate\Tests\Integration\TestCase;

class IntegrationTest extends TestCase
{
    use ProxyTesting;

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

        $headers = $this->getRequestFactory()->getLastHeaders();
        print_r($headers);
    }
}
