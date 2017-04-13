<?php

/**
 * Demo integration test
 *
 * For some reason the curl methods in Spiderling have been playing absolute havoc - post()
 * just does not want to play ball. The error was:
 *
 *    Openbuildings\Spiderling\Exception_Curl: Curl: Download Error: Empty reply from
 *    server, status 0 on url http://127.0.0.1:8090/test.php
 *
 * This happened even without going through the Proximate proxy, so I've given up and am
 * switching to a curl wrapper from Packagist, which worked first time.
 */

namespace Proximate\Tests\Integration;

use Proximate\Tests\Integration\TestCase;
use Proximate\Proxy\Proxy;

class IntegrationTest extends TestCase
{
    use ProxyTesting;

    // @todo Use the ports 10000-65000 based on (current unix time mod 55000) to combat
    // the "Address already in use" problem
    const URL_BASE = 'http://127.0.0.1:8090';
    const URL_PROXY = 'http://127.0.0.1:8082';

    /**
     * @driver simple
     */
    public function testCachesOnSubsequentGetRequest()
    {
        $this->getCurlClient()->setOpt(CURLOPT_PROXY, self::URL_PROXY);

        // First visit should be uncached
        $this->visitPage();
        $this->assertIsLive();

        // Second visit should be cached
        $this->visitPage();
        $this->assertIsCached();
    }

    /**
     * @driver simple
     */
    public function testDoesNotCacheWhenMethodIsChanged()
    {
        $this->getCurlClient()->setOpt(CURLOPT_PROXY, self::URL_PROXY);

        $this->visitPage();
        $this->assertIsLive();

        // If the method is changed, this is still not cached
        $this->postPage();
        $this->assertIsLive();
    }

    protected function visitPage()
    {
        return $this->getCurlClient()->get(self::URL_BASE . '/test.html');
    }

    protected function postPage()
    {
        return $this->getCurlClient()->post(self::URL_BASE . '/test.html');
    }

    protected function assertIsLive()
    {
        $this->assertEquals(
            Proxy::RESPONSE_LIVE,
            $this->getLastHeader(Proxy::RESPONSE_STATUS_HEADER_NAME)
        );
    }

    protected function assertIsCached()
    {
        $this->assertEquals(
            Proxy::RESPONSE_CACHED,
            $this->getLastHeader(Proxy::RESPONSE_STATUS_HEADER_NAME)
        );
    }
}
