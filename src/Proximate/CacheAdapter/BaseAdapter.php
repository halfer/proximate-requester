<?php

/**
 * Defines methods required of a Proximate cache system that are not provided in PHP Cache's
 * cache pool system.
 */

namespace Proximate\CacheAdapter;

interface BaseAdapter
{
    public function countCacheItems();
}
