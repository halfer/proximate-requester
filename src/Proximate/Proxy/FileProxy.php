<?php

/**
 * Some glue code to set up a file-based Proximate proxy server
 */

namespace Proximate\Proxy;

use Socket\Raw\Factory as SocketFactory;

use Proximate\Storage\FilecacheFactory;
use Cache\Adapter\Filesystem\FilesystemCachePool;

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
     * @return self
     */
    public function initSimpleSystem()
    {
        return $this->
            initServer()->
            initFileCache()->
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
     * @return self
     */
    public function initFileCache()
    {
        $factory = new FilecacheFactory($this->rootPath);
        $factory->init();
        $this->cachePool = $factory->getCachePool();
        $this->cacheAdapter = $factory->getCacheAdapter();

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

        $this->getProxy()->setLogger($logger);

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

        $this->getProxy()->setLogger($logger);

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

    /**
     * Gets the cache pool
     *
     * @return FilesystemCachePool
     */
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
