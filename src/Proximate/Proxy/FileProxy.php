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
    protected $serverAddress;
    protected $rootPath;
    protected $socketServer;
    protected $cachePool;
    protected $cacheAdapter;
    protected $proxy;

    public function __construct($serverAddress, $rootPath)
    {
        $this->serverAddress = $serverAddress;
        $this->rootPath = $rootPath;
    }

    /**
     * Sets up a completely vanilla proxy with all the defaults
     *
     * To modify the behaviour of any of these methods, they can be over-ridden in a child
     * class, or a caller can just call the methods it wishes (e.g. to remove the logger).
     *
     * @param string $folder The sub-folder name in which cache items are stored
     * @return self
     */
    public function initSimpleSystem($folder = 'cache')
    {
        return $this->
            initServer()->
            initFileCache($folder)->
            initProxy()->
            addStdoutLogger();
    }

    /**
     * Creates a listening socket
     *
     * @return self
     */
    public function initServer()
    {
        $factory = new SocketFactory();
        $this->socketServer = $factory->createServer($this->serverAddress);

        return $this;
    }

    /**
     * Initialises the file cache
     *
     * @param string $folder The sub-folder name in which cache items are stored
     * @return self
     */
    public function initFileCache($folder = 'cache')
    {
        // This sets up the cache storage system
        $filesystemAdapter = new LocalFileAdapter($this->rootPath);
        $filesystem = new Filesystem($filesystemAdapter);
        $this->cachePool = new FilesystemCachePool($filesystem, $folder);

        // Here is a dependency to perform additional ops on the cache
        $this->cacheAdapter = new FilesystemCacheAdapter($filesystem);
        $this->cacheAdapter->setCacheFolder($folder);

        return $this;
    }

    /**
     * Initialises the proxy system
     *
     * @return self
     */
    public function initProxy()
    {
        // @todo Bomb out if cachePool or cacheAdapter are not set
        // @todo Maybe use getters for all properties, and put the exceptions in there

        $this->proxy = new Proxy($this->socketServer, $this->cachePool, $this->cacheAdapter);
        $this->
            getProxy()->
            checkExtensionsAvailable()->
            handleTerminationSignals();

        return $this;
    }

    public function addStdoutLogger()
    {
        $logger = new Logger('stdout');
        $logger->pushHandler(new ErrorLogHandler());

        $this->getProxy()->addLogger($logger);

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
