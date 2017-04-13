<?php

/**
 * Demo integration test
 */

namespace Proximate\Tests\Integration;

use Proximate\Tests\Integration\TestCase;
use Proximate\Proxy\Proxy;

class IntegrationTest extends TestCase
{
    use ProxyTesting;

    const URL_BASE = 'http://127.0.0.1:8090';
    const URL_PROXY = 'http://127.0.0.1:8082';

    /**
     * @driver simple
     */
    public function testCachesOnSubsequentGetRequest()
    {
        // First visit should be uncached
        $text = $this->visit(self::URL_BASE . '/test.html')->
            find('div')->
            text();
        // Check we're on the right page
        $this->assertContains('Hello', $text);
        $this->assertEquals(
            Proxy::RESPONSE_LIVE,
            $this->getLastHeader(Proxy::RESPONSE_STATUS_HEADER_NAME)
        );

        // Second visit should be cached
        $this->visit(self::URL_BASE . '/test.html');
        $this->assertEquals(
            Proxy::RESPONSE_CACHED,
            $this->getLastHeader(Proxy::RESPONSE_STATUS_HEADER_NAME)
        );
    }
}
