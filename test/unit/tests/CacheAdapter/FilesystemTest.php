<?php

/**
 * Unit tests for the CacheAdapter\Filesystem class
 */

use PHPUnit\Framework\TestCase;
use League\Flysystem\Filesystem as FlysystemAdapter;
use Proximate\CacheAdapter\Filesystem;
use Cache\Adapter\Filesystem\FilesystemCachePool;

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
        $cachePool = Mockery::mock(FilesystemCachePool::class);
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

    public function testSaveResponse()
    {
        $this->markTestIncomplete(); // @todo Missing test
    }

    public function testLoadResponse()
    {
        $this->markTestIncomplete(); // @todo Missing test
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
}
