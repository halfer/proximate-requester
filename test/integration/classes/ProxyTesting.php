<?php

/**
 * A trait to add some useful proxy methods
 */

namespace Proximate\Tests\Integration;

use Proximate\Proxy\FileProxy;
use Curl\Curl;
use Proximate\Client;

trait ProxyTesting
{
    protected $curlClient;
    protected static $CACHE_FOLDER = 'cache';

    /**
     * Gets the value of the requested header from the last HTTP operation
     *
     * @param string $headerName
     * @return string
     */
    protected function getLastHeader($headerName)
    {
        $headers = [];
        foreach ($this->getCurlClient()->response_headers as $header)
        {
            $parts = explode(':', $header, 2);
            if (count($parts) == 2)
            {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                if ($key && $value)
                {
                    $headers[$key] = $value;
                }
            }
        }

        return isset($headers[$headerName]) ? $headers[$headerName] : null;
    }

    /**
     * Starts the proxy in a process fork
     *
     * @param string $serverAddress
     * @param string $cachePath
     */
    protected static function startProxy($serverAddress, $cachePath)
    {
        // Fork into two processes
        $pid = pcntl_fork();

        // We are the child
        if ($pid == 0)
        {
            // Create a temp cache if required
            #$cachePath = '/tmp/proximate-tests';
            @mkdir($cachePath);

            $proxier = new FileProxy($serverAddress, $cachePath);
            // Init a proxy without a logger
            $proxier->
                initServer()->
                initFileCache(self::$CACHE_FOLDER)->
                initProxy()->
                getProxy()->
                enableDebugHeaders()->
                listenLoop();
            exit();
        }
        // We are the parent
        elseif ($pid > 0)
        {
            // The proxy needs some settling down time, maybe we could add a feature into
            // the Proximate\Client to do this better?
            sleep(2);
        }
    }

    /**
     * Wipe the proxy server cache between tests
     */
    public function clearCache($cachePath)
    {
        $proxyPath =
            $cachePath . DIRECTORY_SEPARATOR .
            self::$CACHE_FOLDER . DIRECTORY_SEPARATOR .
            '*';
        foreach(glob($proxyPath) as $file)
        {
            unlink($file);
        }
    }

    /**
     * Set up a new curl client
     */
    public function initCurl()
    {
        $this->curlClient = new Curl();
        $this->getCurlClient()->setOpt(CURLOPT_PROXY, self::getProxyServerUrl());
    }

    /**
     * Gets the current curl instance
     *
     * @return Curl
     */
    protected function getCurlClient()
    {
        return $this->curlClient;
    }

    /**
     * Shuts down the proxy server
     */
    public static function stopProxy($serverAddress)
    {
        $client = new Client($serverAddress);
        $client->fetch('SHUTDOWN');
    }
}
