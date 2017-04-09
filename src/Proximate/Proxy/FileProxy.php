<?php

/**
 * Some glue code to set up a file-based Proximate proxy server
 */

namespace Proximate\Proxy;

use Socket\Raw\Factory as SocketFactory;

use League\Flysystem\Adapter\Local as LocalFileAdapter;
use League\Flysystem\Filesystem;
use Cache\Adapter\Filesystem\FilesystemCachePool;

use Proximate\CacheAdapter\Filesystem as FilesystemCacheAdapter;

use Monolog\Logger;
use Monolog\Handler\ErrorLogHandler;

use Proximate\Proxy\Proxy;

class FileProxy
{
    protected $server;
    protected $rootPath;
    protected $proxy;

    public function __construct($server, $rootPath)
    {
        $this->server = $server;
        $this->rootPath = $rootPath;
    }

    /**
     * Call this to do more heavy-duty setup than we can do in the ctor
     *
     * @param string $folder The sub-folder name in which cache items are stored
     * @return self
     */
    public function setup($folder = 'cache')
    {
        // Here is the basis of the listening system
        $factory = new SocketFactory();
        $server = $factory->createServer($this->server);

        // This sets up the cache storage system
        $filesystemAdapter = new LocalFileAdapter($this->rootPath);
        $filesystem = new Filesystem($filesystemAdapter);
        $cachePool = new FilesystemCachePool($filesystem, $folder);

        // Here is a dependency to perform additional ops on the cache
        $cacheAdapter = new FilesystemCacheAdapter($filesystem);
        $cacheAdapter->setCacheFolder($folder);

        // Here is the optional logger to inject
        $logger = new Logger('stdout');
        $logger->pushHandler(new ErrorLogHandler());

        $this->proxy = new Proxy($server, $cachePool, $cacheAdapter);
        $this->
            getProxy()->
            checkExtensionsAvailable()->
            addLogger($logger)->
            handleTerminationSignals();

        return $this;
    }

    /**
     * Gets the internal proxy instance
     *
     * @return Proxy
     */
    public function getProxy()
    {
        return $this->proxy;
    }
}
