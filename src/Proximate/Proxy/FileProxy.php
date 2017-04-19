<?php

/**
 * Some glue code to set up a file-based Proximate proxy server
 */

namespace Proximate\Proxy;

use Socket\Raw\Factory as SocketFactory;

use League\Flysystem\Adapter\Local as LocalFileAdapter;
use League\Flysystem\Filesystem;
use Cache\Adapter\Filesystem\FilesystemCachePool;

use Proximate\Storage\Filesystem as FilesystemCacheAdapter;

use Monolog\Logger;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\StreamHandler;

use Proximate\Proxy\Proxy;

use Proximate\Exception\Init as InitException;

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
     * class, or a caller can just directly call the methods it wishes (e.g. to remove the
     * logger).
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
        $this->proxy = new Proxy(
            $this->getSocketServer(),
            $this->getCachePool(),
            $this->getCacheAdapter()
        );
        $this->
            getProxy()->
            checkExtensionsAvailable()->
            handleTerminationSignals();

        return $this;
    }

    /**
     * Adds a stdout logger to an existing proxy instance
     *
     * @return self
     */
    public function addStdoutLogger()
    {
        $logger = new Logger('stdout');
        $logger->pushHandler(new ErrorLogHandler());

        $this->getProxy()->addLogger($logger);

        return $this;
    }

    /**
     * Adds a file logger to an existing proxy instance
     *
     * @return self
     */
    public function addFileLogger($file)
    {
        $logger = new Logger('file');
        $logger->pushHandler(new StreamHandler($file));

        $this->getProxy()->addLogger($logger);

        return $this;
    }

    public function getSocketServer()
    {
        if (!$this->socketServer)
        {
            throw new InitException("Socket server not initialised");
        }

        return $this->socketServer;
    }

    public function getCachePool()
    {
        if (!$this->cachePool)
        {
            throw new InitException("Cache pool not initialised");
        }

        return $this->cachePool;
    }

    public function getCacheAdapter()
    {
        if (!$this->cacheAdapter)
        {
            throw new InitException("Cache adapter not initialised");
        }

        return $this->cacheAdapter;
    }

    /**
     * Gets the internal proxy instance
     *
     * @return Proxy
     */
    public function getProxy()
    {
        if (!$this->proxy)
        {
            throw new InitException("Proxy not initialised");
        }

        return $this->proxy;
    }
}
