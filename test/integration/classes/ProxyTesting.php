<?php

/**
 * A trait to add some useful proxy methods
 */

namespace Proximate\Tests\Integration;

//use Openbuildings\Spiderling\Driver_Simple;
use Curl\Curl;
use Proximate\Client;

trait ProxyTesting
{
    protected $PROXY_CACHE_PATH = '/tmp/proximate-tests/cache';
    protected $curlClient;

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
     * Turns on the proxy server
     *
     * @todo Can we use fork() here instead of an external script?
     * @todo Pass a cache path to this script ($this->PROXY_CACHE_PATH)
     */
    protected static function startProxy()
    {
        $root = realpath(__DIR__ . '/../../..');
        $command = "php {$root}/test/integration/scripts/proxy.php >/dev/null &";

        $output = $return = null;
        exec($command, $output, $return);
        if ($return)
        {
            throw new \Exception(
                "Could not start the proxy server script"
            );
        }

        // The proxy needs some settling down time, maybe we could add a feature into
        // the Proximate\Client to do this better?
        sleep(2);
    }

    /**
     * Wipe the proxy server cache between tests
     */
    public function clearCache($cachePath)
    {
        foreach(glob($cachePath . '/*') as $file)
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
    public static function stopProxy()
    {
        $client = new Client(self::URL_PROXY);
        $client->fetch('SHUTDOWN');
    }
}
