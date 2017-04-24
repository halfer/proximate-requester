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
    const PROXY_CACHE_PATH = '/tmp/proximate-tests/cache';

    protected static $proxyUrl;

    public function testUncachedGet()
    {
        $curl = $this->visitPage('method.php');
        $this->assertEquals('GET', $curl->response, 'Ensure a simple fetch uses GET');
    }

    public function testUncachedPost()
    {
        $curl = $this->postPage([], 'method.php');
        $this->assertEquals('POST', $curl->response, 'Ensure a simple fetch uses POST');
    }

    public function testPostVariables()
    {
        $vars = ['hello' => '123', ];
        $curl = $this->postPage($vars, 'post-vars.php');
        $this->assertEquals(
            json_encode($vars),
            $curl->response,
            'Ensure POST vars reach the target'
        );
    }

    /**
     * @driver simple
     */
    public function testCachesOnSubsequentGetRequest()
    {
        // First visit should be uncached
        $this->visitPage();
        $this->assertIsLive('A get call should not be cached');

        // Second visit should be cached
        $this->visitPage();
        $this->assertIsCached('Calling the same again should have cached it');
    }

    /**
     * @driver simple
     */
    public function testDoesNotCacheWhenMethodIsChanged()
    {
        $this->visitPage();
        $this->assertIsLive('Checks that a GET is fetched live');

        // If the method is changed, this is still not cached
        $this->postPage();
        $this->assertIsLive('Checks that a POST is fetched live, since the method has changed');
    }

    public function testDoesNotCacheWhenPostVarsChange()
    {
        $vars = ['A' => '1', 'B' => '2', ];
        $this->postPage($vars);
        $this->assertIsLive('Checks that an initial POST is fetched live');

        // This will be cached
        $this->postPage($vars);
        $this->assertIsCached('Checks that the same POST is cached');

        // This won't be cached, as the data is different
        $this->postPage(['A' => '1', 'B' => '3', ]);
        $this->assertIsLive('Checks that a new POST is fetched live');
    }

    protected function visitPage($leaf = 'test.html')
    {
        return $this->getCurlClient()->get($this->getWebServerUrl() . '/' . $leaf);
    }

    protected function postPage(array $data = [], $leaf = 'test.html')
    {
        return $this->getCurlClient()->post(
            $this->getWebServerUrl() . '/' . $leaf,
            $data
        );
    }

    protected function assertIsLive($message)
    {
        $this->assertEquals(
            Proxy::RESPONSE_LIVE,
            $this->getLastHeader(Proxy::RESPONSE_STATUS_HEADER_NAME),
            $message
        );
    }

    protected function assertIsCached($message)
    {
        $this->assertEquals(
            Proxy::RESPONSE_CACHED,
            $this->getLastHeader(Proxy::RESPONSE_STATUS_HEADER_NAME),
            $message
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

    protected static function getProxyServerUrl()
    {
        return self::$proxyUrl;
    }
}
