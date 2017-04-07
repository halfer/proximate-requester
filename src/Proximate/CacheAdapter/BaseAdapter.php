<?php

/**
 * Defines methods required of a Proximate cache system that are not provided in PHP Cache's
 * cache pool system.
 */

namespace Proximate\CacheAdapter;

use Psr\Cache\CacheItemPoolInterface;

abstract class BaseAdapter
{
    protected $metadata = [];

    /**
     * Returns the number of items in the cache
     */
    abstract public function countCacheItems();

    /**
     * Returns all of the keys in the cache
     */
    abstract protected function getCacheKeys();

    /**
     * The simplest possible response saver, just saves it as-is with no metadata
     *
     * @param string $response
     * @param array $metadata Any metadata relating to this response
     * @return string
     */
    public function saveResponse($response, array $metadata)
    {
        return $response;
    }

    /**
     * The simplest possible response loader, just loads it as-is
     *
     * @param string $cachedString
     * @return string
     */
    public function loadResponse($cachedString)
    {
        return $cachedString;
    }

    /**
     * Gets a page's worth of cache keys
     *
     * @param integer $pageNo
     * @param integer $itemsPerPage
     * @return array
     */
    public function getPageOfCacheKeys($pageNo, $itemsPerPage)
    {
        $items = $this->getCacheKeys();
        $page = array_slice($items, ($pageNo - 1) * $itemsPerPage, $itemsPerPage);

        return $page;
    }

    /**
     * Returns a traversable set of cache items
     *
     * @param CacheItemPoolInterface $cachePool
     * @param integer $pageNo
     * @param integer $itemsPerPage
     * @return array
     */
    public function getPageOfCacheItems(CacheItemPoolInterface $cachePool, $pageNo, $itemsPerPage)
    {
        $keys = $this->getPageOfCacheKeys($pageNo, $itemsPerPage);
        $items = $cachePool->getItems($keys);

        return $items;
    }
}
