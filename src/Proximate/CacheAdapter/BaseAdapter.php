<?php

/**
 * Defines methods required of a Proximate cache system that are not provided in PHP Cache's
 * cache pool system.
 */

namespace Proximate\CacheAdapter;

abstract class BaseAdapter
{
    protected $metadata = [];

    abstract public function countCacheItems();

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
}
