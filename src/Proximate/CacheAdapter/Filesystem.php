<?php

/** 
 * Defines the filesystem adapter for Proximate
 */

namespace Proximate\CacheAdapter;

use League\Flysystem\Filesystem as FlysystemAdapter;
use Proximate\Exception\Server;

class Filesystem extends BaseAdapter
{
    protected $flysystemAdapter;

    public function __construct(FlysystemAdapter $flysystemAdapter)
    {
        $this->flysystemAdapter = $flysystemAdapter;
    }

    public function countCacheItems()
    {
        $count = count($this->getCacheKeys());

        return $count;
    }

    /**
     * Returns all cache keys
     *
     * If the cache might get big, we could swap this for a generator, to conserve memory?
     *
     * @return array
     */
    protected function getCacheKeys()
    {
        return $this->getFlysystemAdapter()->listContents('cache');
    }

    /**
     * Converts a response string and metadata array to a saveable string
     *
     * Note we do not do any serialisation here, the cache will do that.
     *
     * @param string $response
     * @param array $metadata
     * @return array
     * @throws Server
     */
    public function saveResponse($response, array $metadata)
    {
        foreach (['url', 'method', 'key'] as $key)
        {
            if (!isset($metadata[$key]))
            {
                throw new Server(
                    sprintf("Expecting a '%s' metadata item when saving", $key)
                );
            }
        }

        return [
            'url' => $metadata['url'],
            'method' => $metadata['method'],
            'key' => $metadata['key'],
            'response' => $response,
        ];
    }

    /**
     * Retrieves the response from the cache
     *
     * Note we do not do any de-serialisation here, the cache will do that. Ideally I'd
     * strongly type the parameter here, but since PHP doesn't like it when a method
     * signature disagrees with the parent implementation, I will leave it un-hinted.
     *
     * @param array $cachedData
     * @return string
     */
    public function loadResponse($cachedData)
    {
        if (!isset($cachedData['response']))
        {
            throw new Server("This cache entry does not have a 'response' key");
        }

        return $cachedData['response'];
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
