<?php

/**
 * Unit tests for the CacheAdapter\BaseAdapter class
 *
 * We don't need to test the pagination methods as they are tested in the FilesystemTest
 */

use PHPUnit\Framework\TestCase;
use Proximate\CacheAdapter\BaseAdapter;

class BaseAdapterTest extends TestCase
{
    public function testConvertResponseToCache()
    {
        $response = "This is a response";
        $this->assertEquals(
            $response,
            $this->getCacheAdapter()->convertResponseToCache($response, [])
        );
    }

    public function testConvertCacheToResponse()
    {
        $cachedData = "This is a cached value";
        $this->assertEquals(
            $cachedData,
            $this->getCacheAdapter()->convertCacheToResponse($cachedData)
        );
    }

    protected function getCacheAdapter()
    {
        return new DummyAdapter();
    }
}

class DummyAdapter extends BaseAdapter
{
    public function countCacheItems()
    {
        return 0;
    }

    public function getCacheKeys()
    {
        return [];
    }
}
