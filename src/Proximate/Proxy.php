<?php

/**
 * Class to scan headers and determine whether to modify the required protocol
 *
 * @todo Add a thin wrapper around curl to increase testability
 * @todo Add shutdown handler to shutdown the socket
 */

namespace Proximate;

use Socket\Raw\Socket;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Monolog\Logger;

class Proxy
{
    const REAL_URL_HEADER_NAME = 'X-Real-Url';

    protected $server;
    protected $client;
    protected $cachePool;
    protected $logger;
    protected $writeBuffer;
    protected $realUrlHeaderName = self::REAL_URL_HEADER_NAME;

    public function __construct(Socket $serverSocket, CacheItemPoolInterface $cachePool)
    {
        $this->server = $serverSocket;
        $this->cachePool = $cachePool;
    }

    /**
     * Initialises the server listening
     *
     * @todo Swap the die() exits for specialised exceptions
     *
     * @return $this
     */
    public function checkSocketsAvailable()
    {
        foreach (['sockets', ] as $module)
        {
            if (!extension_loaded($module))
            {
                $message = sprintf("Error: extension `%s` not loaded\n", $module);
                $this->log($message, Logger::CRITICAL);
                die($message);
            }
        }

        return $this;
    }

    /**
     * Sets up a PSR-compliant logger on this class
     *
     * @param LoggerInterface $logger
     * @return $this
     */
    public function addLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Default HTTP listening loop
     */
    public function listenLoop()
    {
        $this->log("Starting proxy listener");

        while($this->client = $this->getServerSocket()->accept())
        {
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
     * @param string $input
     */
    protected function handleHttpsConnect($input)
    {
        $this->log("HTTPS proxying not supported", Logger::ERROR);

        $this->writeDataToClient("HTTP/1.1 500 Server error\r\n\r\n");
    }

    /**
     * We've received an HTTP connection
     *
     * @todo Don't fetch and save page if it is already cached
     *
     * @param string $input
     */
    protected function handleHttpConnect($input)
    {
        $url = $this->getTargetUrlFromProxyRequest($input);
        $method = $this->getMethodFromProxyRequest($input);
        $this->log("URL is $url, method is $method");

        // Swap to the real URL if it is provided
        if ($realUrl = $this->checkRealUrlHeader($input))
        {
            $url = $realUrl;
            $this->log("HTTPS URL detected passed in header: $url");
        }

        // @todo Add headers to this
        $ok = $this->fetch($url, $method);

        if ($ok)
        {
            $targetSiteData = $this->assembleOutput(
                $this->implodeHeaders(
                    $this->filterHeaders(
                        $this->getHeaders($this->getOutputBuffer())
                    )
                ),
                $this->getBody($this->getOutputBuffer())
            );

            // Show debug output
            if (strpos($targetSiteData, 'HTTP/1.1 200 OK') !== false)
            {
                $this->log(
                    sprintf("Fetched %d bytes from target site", strlen($targetSiteData))
                );
            }

            // Create a cache key and save the page data
            $key = $this->createCacheKey($url, $method);
            $this->log(
                sprintf("Saving response against cache key `%s`", $key)
            );
            $this->savePage($key, $targetSiteData);
        }
        else
        {
            // Is there a better way to handle an error condition?
            $targetSiteData = "HTTP/1.1 500 Server error\r\n\r\n";
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
        curl_setopt_array($curl, $this->getCurlOpts($method));
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

    /**
     * Gets an array of curl options
     *
     * We need to keep auto-follow off, unless we change the code to feed the fetch site
     * response back to the requesting client.
     *
     * @return array
     */
    protected function getCurlOpts($method)
    {
        if ($method == 'GET')
        {
            $additional = [];
        }
        elseif ($method == 'POST')
        {
            $additional['CURLOPT_POST'] = 1;
        }
        else
        {
            // @todo Add custom method
            // http://stackoverflow.com/q/13420952
            $additional = [];
        }

        return [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 1,
            CURLOPT_VERBOSE => 1,
            CURLOPT_HTTPHEADER => $this->getRequestHeaders(),
            CURLOPT_TIMEOUT => 15,
            CURLOPT_NOPROGRESS => 1,
            CURLOPT_VERBOSE => 0,
            CURLOPT_AUTOREFERER => 1,
            CURLOPT_FOLLOWLOCATION => 0,
            CURLOPT_WRITEFUNCTION => [$this, 'outputToBuffer'],
        ] + $additional;
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
     * Parses the input to get the HTTP method
     *
     * @param string $input
     * @return string
     */
    protected function getMethodFromProxyRequest($input)
    {
        return $this->parseProxyRequest($input, 1);
    }

    /**
     * Parses the input to get the URL in the proxy fetch command
     *
     * e.g.
     *
     * GET http://ilovephp.jondh.me.uk/en/tutorial/make-your-own-blog HTTP/1.1
     *
     * results in
     *
     * http://ilovephp.jondh.me.uk/en/tutorial/make-your-own-blog
     *
     * @param string $input
     * @return string
     */
    protected function getTargetUrlFromProxyRequest($input)
    {
        return $this->parseProxyRequest($input, 2);
    }

    protected function parseProxyRequest($input, $item)
    {
        $request = null;
        preg_match("'^([^\s]+)\s([^\s]+)\s([^\r\n]+)'ims", $input, $request);
        $element = $request[$item];

        return $element;
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

    protected function getHeaders($output)
    {
        $sections = explode("\r\n\r\n", $output, 2);
        $headers = explode("\r\n", $sections[0]);

        return $headers;
    }

    /**
     * Gets the body section from the output
     *
     * @todo This will fail in the case of timeout, fix this
     *
     * @param string $output
     * @return string
     */
    protected function getBody($output)
    {
        $sections = explode("\r\n\r\n", $output, 2);
        $body = $sections[1];

        return $body;
    }

    protected function implodeHeaders(array $headers) {
        return implode(
            "\r\n",
            $headers
        ) .
        "\r\n";
    }

    /**
     * Removes headers that will bork when received
     *
     * Cache-Control makes no difference if removed.
     *
     * Aha, Transfer-Encoding: chunked breaks a wget fetch (presumably because now it is no longer
     * chunked)
     *
     * @todo Worth adding Connection: Keep-Alive?
     *
     * @param array $headers
     */
    protected function filterHeaders(array $headers)
    {
        return array_filter($headers, function($element) {
            return strpos($element, 'Transfer-Encoding') !== 0;
        });
    }

    protected function assembleOutput($headers, $body)
    {
        return $headers . "\r\n" . $body;
    }

    /**
     * Creates a cache key for page metadata
     *
     * Should we consider query strings or POST parameters?
     *
     * @param string $url
     * @param string $method
     */
    protected function createCacheKey($url, $method)
    {
        return sha1($method . $url);
    }

    /**
     * Save the page to the cache system
     *
     * @param string $key
     * @param string $response
     */
    protected function savePage($key, $response)
    {
        $item = $this->getCachePool()->getItem($key);
        $item->set($response);
        $this->getCachePool()->save($item);
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
     * Logs a message if a logger has been provided, otherwise ignores log request
     *
     * @param string $message
     * @param integer $level
     */
    protected function log($message, $level = Logger::INFO)
    {
        if ($logger = $this->logger)
        {
            /* @var $logger LoggerInterface */
            $logger->log($level, $message);
        }
    }
}
