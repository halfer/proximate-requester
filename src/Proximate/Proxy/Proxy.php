<?php

/**
 * Class to scan headers and determine whether to modify the required protocol
 *
 * @todo Set up Travis HTTP and HTTPS tests
 * @todo Add a thin wrapper around curl to increase testability
 * @todo Make the non-blocking sleep delay configurable
 */

namespace Proximate\Proxy;

use Socket\Raw\Socket;
use Psr\Cache\CacheItemPoolInterface;
use Proximate\CacheAdapter\BaseAdapter as CacheAdapter;
use Proximate\Exception\Init as InitException;
use Monolog\Logger;

class Proxy
{
    use \Proximate\Logger;
    use \Proximate\Feature\Curl;
    use \Proximate\Feature\RequestParser;
    use \Proximate\Feature\ResponseParser;

    const REAL_URL_HEADER_NAME = 'X-Real-Url';

    protected $server;
    protected $client;
    protected $cachePool;
    protected $cacheAdapter;
    protected $dispatchPcntlSigs = false;
    protected $exit = false;
    protected $writeBuffer;
    protected $realUrlHeaderName = self::REAL_URL_HEADER_NAME;

    public function __construct(Socket $serverSocket, CacheItemPoolInterface $cachePool, CacheAdapter $cacheAdapter)
    {
        $this->server = $serverSocket;
        $this->cachePool = $cachePool;
        $this->cacheAdapter = $cacheAdapter;
    }

    /**
     * Checks the required extensions are available
     *
     * @todo Add these deps to the composer.json file as well
     *
     * @return $this
     */
    public function checkExtensionsAvailable()
    {
        foreach (['sockets', 'pcntl', 'curl', ] as $module)
        {
            if (!extension_loaded($module))
            {
                $message = sprintf("Error: extension `%s` not loaded\n", $module);
                $this->log($message, Logger::CRITICAL);
                throw new InitException($message);
            }
        }

        return $this;
    }

    public function handleTerminationSignals()
    {
        $this->dispatchPcntlSigs = true;

        pcntl_signal(SIGINT, [$this, 'closeServer']);
        pcntl_signal(SIGTERM, [$this, 'closeServer']);

        return $this;
    }

    /**
     * Close the server down in the case of user exit
     *
     * @todo I'm not actually sure this closes the connection, sometimes when reconnecting
     * it takes ~30 sec for the port to be ready to listen on again
     */
    public function closeServer()
    {
        $this->exit = true;
        $this->getServerSocket()->shutdown()->close();
        $this->log('Closing server connection before exiting');
    }

    /**
     * Default HTTP listening loop
     */
    public function listenLoop()
    {
        $this->log(
            sprintf(
                "Starting proxy listener on %s",
                $this->getServerSocket()->getSockName()
            )
        );
        $this->getServerSocket()->setBlocking(false);

        while(true)
        {
            // Ensures we do not read from a closed socket
            if ($this->exit)
            {
                break;
            }

            // Here's a non-blocking socket read
            if (!$this->getServerSocket()->selectRead())
            {
                usleep(10000);
                if ($this->dispatchPcntlSigs)
                {
                    pcntl_signal_dispatch();
                }
                continue;
            }
            $this->client = $this->getServerSocket()->accept();

            // The buffer size should be enough to accommodate the request - 4K should be fine
            $input = $this->getClientSocket()->read(1024 * 4);

            $match = null;
            if(preg_match("'^CONNECT ([^ ]+):(\d+) '", $input, $match)) // HTTPS
            {
                $this->handleHttpsConnect($input);
            }
            else
            {
                $this->handleHttpConnect($input);
            }

            $this->getClientSocket()->close();
        }
    }

    /**
     * We've received an HTTPS connection
     *
     * @todo Can the 500 error be more explicit for the client's benefit?
     *
     * @param string $request
     */
    protected function handleHttpsConnect($request)
    {
        $this->log("HTTPS proxying not supported", Logger::ERROR);

        $this->writeDataToClient("HTTP/1.1 500 Server error\r\n\r\n");
    }

    /**
     * We've received an HTTP connection
     *
     * @param string $request
     */
    protected function handleHttpConnect($request)
    {
        $url = $this->getTargetUrlFromProxyRequest($request);
        $method = $this->getMethodFromProxyRequest($request);
        $this->log("URL is $url, method is $method");

        // Swap to the real URL if it is provided
        if ($realUrl = $this->checkRealUrlHeader($request))
        {
            $url = $realUrl;
            $this->log("HTTPS URL detected passed in header: $url");
        }

        // Check if cache key exists
        $key = $this->getCacheAdapter()->createCacheKey($request, $url);
        $cacheItem = $this->getCachePool()->getItem($key);

        if ($cacheItem->isHit())
        {
            // If it does then read it here
            $cacheData = $cacheItem->get();
            $targetSiteData = $this->getCacheAdapter()->convertCacheToResponse($cacheData);
            $this->log(
                sprintf(
                    "Retrieved page of %d bytes from cache against key %s",
                    strlen($targetSiteData),
                    $key
                )
            );
        }
        else
        {
            // @todo Add headers to this (e.g. User Agent)
            $ok = $this->fetch($url, $method);
            if ($ok)
            {
                $targetSiteData = $this->saveToCache(
                    $key,
                    ['url' => $url, 'method' => $method, 'key' => $key, ]
                );
            }
            else
            {
                $targetSiteData = $this->getFailureResponse();
           }
        }

        $this->writeDataToClient($targetSiteData);
    }

    protected function writeDataToClient($data)
    {
        $this->getClientSocket()->write($data);
    }

    /**
     * Fetches the proxied site
     *
     * I'm logging an error here rather than throwing an exception, since I don't want to
     * cause the listening loop to break permanently.
     *
     * @param string $url
     * @param string $method
     * @return boolean
     */
    public function fetch($url, $method)
    {
        $this->resetBuffer();

        $curl = curl_init($url);
        curl_setopt_array(
            $curl,
            $this->getCurlOptsCustom($method)
        );
        $result = curl_exec($curl);

        if ($result === false) {
            $this->log(
                sprintf(
                    "Fetch error: %s\n",
                    curl_strerror(curl_errno($curl))
                ),
                Logger::ERROR
            );
        }

        curl_close($curl);

        // Includes headers
        return $result !== false;
    }

    protected function saveToCache($key, array $metadata)
    {
        $targetSiteData = $this->assembleOutput(
            $this->implodeHeaders(
                $this->filterHeaders(
                    $this->getHeaders($this->getOutputBuffer())
                )
            ),
            $this->getBody($this->getOutputBuffer())
        );

        // Convert the response to whatever the cache format is
        $cacheData = $this->getCacheAdapter()->convertResponseToCache(
            $targetSiteData,
            $metadata
        );

        // Save item to the cache
        $item = $this->getCachePool()->getItem($key);
        $item->set($cacheData);
        $this->getCachePool()->save($item);

        // This logs the size of the page rather than the whole meta block
        $this->log(
            sprintf(
                "Fetched page of %d bytes and saving against cache key `%s`",
                strlen($targetSiteData),
                $key
            )
        );

        $this->log(
            sprintf("The cache now contains %d items", $this->getCacheAdapter()->countCacheItems())
        );

        return $targetSiteData;
    }

    // Is there a better way to handle an error condition?
    protected function getFailureResponse()
    {
        $this->log("Failed to load requested site", Logger::ERROR);
        $targetSiteData = "HTTP/1.1 500 Server error\r\n\r\n";

        return $targetSiteData;
    }

    /**
     * Gets an array of curl options
     *
     * We need to keep auto-follow off, unless we change the code to feed the fetch site
     * response back to the requesting client.
     *
     * @return array
     */
    protected function getCurlOptsCustom($method)
    {
        return
            $this->getCurlOpts($method) +
            [
                CURLOPT_HTTPHEADER => $this->getRequestHeaders(),
                CURLOPT_FOLLOWLOCATION => 0,
                CURLOPT_WRITEFUNCTION => [$this, 'outputToBuffer'],
            ];
    }

    protected function getOutputBuffer()
    {
        return $this->writeBuffer;
    }

    public function outputToBuffer($curl, $data)
    {
        $this->writeBuffer .= $data;

        return strlen($data);
    }

    protected function resetBuffer()
    {
        $this->writeBuffer = '';
    }

    /**
     * Supplies a list of headers to the proxied HTTP fetch
     *
     * @todo Worth adding a setter to customise this?
     *
     * @return array
     */
    protected function getRequestHeaders()
    {
        return [];
    }

    /**
     * Returns URL if real URL is present, or null
     *
     * @param string $input
     */
    protected function checkRealUrlHeader($input)
    {
        $lineStart = '^';
        $whitespace = '\\s*';
        $header = self::REAL_URL_HEADER_NAME;
        $nonWhitespace = '[^ \\r\\n]+';
        $regex = "#^{$lineStart}{$header}:{$whitespace}({$nonWhitespace})#m";

        // Search the headers for a "real URL" declaration
        $matches = null;
        $ok = preg_match($regex, $input, $matches);
        $url = $ok ? $matches[1] : null;

        return $url;
    }

    protected function assembleOutput($headers, $body)
    {
        return $headers . "\r\n" . $body;
    }

    /**
     * Returns the current server socket instance
     *
     * @return Socket
     */
    protected function getServerSocket()
    {
        return $this->server;
    }

    /**
     * Returns the current client socket instance
     *
     * @return Socket
     */
    protected function getClientSocket()
    {
        return $this->client;
    }

    /**
     * Gets the cache so items can be added to it
     *
     * @return CacheItemPoolInterface
     */
    protected function getCachePool()
    {
        return $this->cachePool;
    }

    /**
     * Gets the cache adapter for the cache
     *
     * @return CacheAdapter
     */
    protected function getCacheAdapter()
    {
        return $this->cacheAdapter;
    }
}
