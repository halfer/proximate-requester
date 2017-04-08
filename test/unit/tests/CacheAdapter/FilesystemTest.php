<?php

/**
 * Unit tests for the CacheAdapter\Filesystem class
 */

use PHPUnit\Framework\TestCase;
use League\Flysystem\Filesystem as FlysystemAdapter;
use Proximate\CacheAdapter\Filesystem;
use Cache\Adapter\Filesystem\FilesystemCachePool;
use Cache\Adapter\Common\CacheItem;

class FilesystemTest extends TestCase
{
    protected $flysystem;

    /**
     * Create a mocked flysystem for all tests
     */
    public function setUp()
    {
        $this->flysystem = Mockery::mock(FlysystemAdapter::class);
    }

    public function testCountCacheItems()
    {
        $cacheKeys = $this->getSmallCacheKeySet();
        $this->setCacheListExpectation($cacheKeys);
        $this->assertEquals(count($cacheKeys), $this->getCacheAdapter()->countCacheItems());
    }

    public function testGetAllCacheKeys()
    {
        $cacheKeys = $this->getSmallCacheKeySet();
        $this->setCacheListExpectation($cacheKeys);
        $this->assertEquals($cacheKeys, $this->getCacheAdapter()->getPageOfCacheKeys(1, 10));
    }

    protected function getSmallCacheKeySet()
    {
        return [1, 2, 3, ];
    }

    /**
     * Tests that pagination works
     *
     * @dataProvider paginationDataProvider
     * @param integer $page
     * @param array $expectedResult
     */
    public function testPagination($page, array $expectedResult)
    {
        $cacheKeys = $this->getLargeCacheKeySet();
        $this->setCacheListExpectation($cacheKeys);
        $this->assertEquals(
            $expectedResult,
            $this->getCacheAdapter()->getPageOfCacheKeys($page, 3)
        );
    }

    public function testGetCacheItems()
    {
        $cacheKeys = $this->getSmallCacheKeySet();
        $this->setCacheListExpectation($cacheKeys);

        // Set up mock for cache pool
        $cacheItems = ['Item 1', 'Item 2', 'Item 3', ];
        $cachePool = $this->getMockedCache();
        $cachePool->
            shouldReceive('getItems')->
            with($cacheKeys)->
            andReturn($cacheItems);

        $result = $this->
            getCacheAdapter()->
            setCacheItemPoolInterface($cachePool)->
            getPageOfCacheItems(1, count($cacheItems));
        $this->assertEquals($cacheItems, $result);
    }

    public function paginationDataProvider()
    {
        return [
            [1, [1, 2, 3, ]], // Test first page
            [2, [4, 5, 6, ]], // Test subsequent page
            [3, [7, ]],       // Test a partial page
        ];
    }

    protected function getLargeCacheKeySet()
    {
        return [1, 2, 3, 4, 5, 6, 7, ];
    }

    public function testConvertResponseToCache()
    {
        $response = "This is a response";
        $metadata = $this->getDemoMetadata();
        $converted = $this->getCacheAdapter()->convertResponseToCache($response, $metadata);

        $expected = array_merge($metadata, ['response' => $response]);
        $this->assertEquals($expected, $converted);
    }

    /**
     * Responses with missing metadata data
     *
     * @dataProvider missingMetadataKeyDataProvider
     * @expectedException Proximate\Exception\Server
     */
    public function testBadConvertResponseToCache($missingKey)
    {
        $response = "This is a response";
        $metadata = $this->getDemoMetadata();

        // Emulate this key not being set, so an error is thrown
        unset($metadata[$missingKey]);

        $this->getCacheAdapter()->convertResponseToCache($response, $metadata);
    }

    protected function getDemoMetadata()
    {
        return [
            'url' => 'http://example.com/page',
            'method' => 'GET',
            'key' => 'mykey',
        ];
    }

    public function missingMetadataKeyDataProvider()
    {
        return [
            ['url'],
            ['method'],
            ['key'],
        ];
    }

    public function testConvertCacheToResponse()
    {
        $response = "This is a response";
        $metadata = $this->getDemoMetadata();
        $cachedData = array_merge(
            $metadata,
            ['response' => $response, ]
        );

        $converted = $this->getCacheAdapter()->convertCacheToResponse($cachedData);
        $this->assertEquals($response, $converted);
    }

    public function testReadCacheItem()
    {
        $key = 'Key A';
        $valueExpected = ['Cache Item A'];

        // Mock the cache item
        $cacheItem = Mockery::mock(CacheItem::class);
        $cacheItem->
            shouldReceive('get')->
            once()->
            andReturn($valueExpected);

        // Mock the cache pool
        $cachePool = $this->getMockedCache();
        $cachePool->
            shouldReceive('getItem')->
            once()->
            with($key)->
            andReturn($cacheItem);

        $cacheItemResult = $this->
            getCacheAdapter()->
            setCacheItemPoolInterface($cachePool)->
            readCacheItem($key);
        $this->assertEquals($valueExpected, $cacheItemResult);
    }

    public function testExpireCacheItem()
    {
        $key = 'Key B';

        // Mock the cache pool
        $cachePool = $this->getMockedCache();
        $cachePool->
            shouldReceive('deleteItem')->
            once()->
            with($key);

        $this->
            getCacheAdapter()->
            setCacheItemPoolInterface($cachePool)->
            expireCacheItem($key);

        // Dummy test to keep PHPUnit quiet, the once() is the real test
        $this->assertEquals(1, 1);
    }

    protected function getCacheAdapter()
    {
        return new Filesystem($this->getMockedFlysystem());
    }

    protected function setCacheListExpectation(array $cacheKeys)
    {
        $this->
            getMockedFlysystem()->
            shouldReceive('listContents')->
            with('cache')->
            andReturn($cacheKeys);
    }

    protected function getMockedFlysystem()
    {
        return $this->flysystem;
    }

    protected function getMockedCache()
    {
        return Mockery::mock(FilesystemCachePool::class);
    }
}
