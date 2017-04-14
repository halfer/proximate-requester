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
    use PortChooser;

    // @todo Use these consts as a basis for the generated URLs
    const URL_PROXY_BASE = '127.0.0.1';
    const URL_PROXY_PORT_MIN = 35000;
    const URL_PROXY_PORT_MAX = 49999;
    const PROXY_CACHE_PATH = '/tmp/proximate-tests';
    const PROXY_CACHE_FOLDER = 'cache';

    protected static $proxyUrl;

    /**
     * @driver simple
     */
    public function testCachesOnSubsequentGetRequest()
    {
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
        $this->visitPage();
        $this->assertIsLive();

        // If the method is changed, this is still not cached
        $this->postPage();
        $this->assertIsLive();
    }

    public function testDoesNotCacheWhenPostVarsChange()
    {
        $vars = ['A' => '1', 'B' => '2', ];
        $this->postPage($vars);
        $this->assertIsLive();

        // This will be cached
        $this->postPage($vars);
        $this->assertIsCached();

        // This won't be cached, as the data is different
        $this->postPage(['A' => '1', 'B' => '3', ]);
        $this->assertIsLive();
    }

    protected function visitPage()
    {
        return $this->getCurlClient()->get($this->getWebServerUrl() . '/test.html');
    }

    protected function postPage(array $data = [])
    {
        return $this->getCurlClient()->post(
            $this->getWebServerUrl() . '/test.html',
            $data
        );
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

    public static function setupBeforeClass()
    {
        // Choose URLs based on random ports
        $proxyPort = self::choosePort(self::URL_PROXY_PORT_MIN, self::URL_PROXY_PORT_MAX);
        self::$proxyUrl = self::URL_PROXY_BASE . ':' . $proxyPort;

        self::startProxy(self::getProxyServerUrl(), self::PROXY_CACHE_PATH);
    }

    public function setUp()
    {
        $this->clearCache(self::PROXY_CACHE_PATH);
        $this->initCurl();
    }

    public static function tearDownAfterClass()
    {
        self::stopProxy(self::getProxyServerUrl());
    }

    protected function getWebServerUrl()
    {
        return TestListener::getWebServerUrl();
    }

    protected static function getProxyServerUrl()
    {
        return self::$proxyUrl;
    }
}
