<?php

/** 
 * Defines the filesystem adapter for Proximate
 */

namespace Proximate\CacheAdapter;

use League\Flysystem\Filesystem as FlysystemAdapter;

class Filesystem implements BaseAdapter
{
    protected $flysystemAdapter;

    public function __construct(FlysystemAdapter $flysystemAdapter)
    {
        $this->flysystemAdapter = $flysystemAdapter;
    }

    public function countCacheItems()
    {
        $items = $this->getFlysystemAdapter()->listContents('cache');
        $count = count($items);

        return $count;
    }

    /**
     * Gets the Flysystem adapter instance
     *
     * @return FlysystemAdapter
     */
    protected function getFlysystemAdapter()
    {
        return $this->flysystemAdapter;
    }
}
