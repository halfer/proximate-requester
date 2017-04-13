<?php

/**
 * A trait to add some useful proxy methods
 */

namespace Proximate\Tests\Integration;

use Openbuildings\Spiderling\Driver_Simple;
use Proximate\Client;

trait ProxyTesting
{
    protected $PROXY_CACHE_PATH = '/tmp/proximate-tests/cache';

    protected $requestFactory;

    public function driver_simple() : Driver_Simple
    {
        $driver = new Driver_Simple();
        $requestFactory = new HTTP();
        $requestFactory->setProxyAddress(self::URL_PROXY);
        $driver->request_factory($requestFactory);
        $this->setRequestFactory($requestFactory);

        return $driver;
    }

    protected function setRequestFactory($requestFactory)
    {
        $this->requestFactory = $requestFactory;
    }

    /**
     * Gets the request factory for this request
     *
     * @return HTTP
     */
    protected function getRequestFactory()
    {
        return $this->requestFactory;
    }

    /**
     * Gets the value of the requested header from the last HTTP operation
     *
     * @todo Put a guard clause around the request factory fetch
     *
     * @param string $headerName
     * @return string
     */
    protected function getLastHeader($headerName)
    {
        $headers = $this->getRequestFactory()->getLastHeaders();

        return isset($headers[$headerName]) ? $headers[$headerName] : null;
    }

    /**
     * Turns on the proxy server
     *
     * @todo Pass a cache path to this script ($this->PROXY_CACHE_PATH)
     */
    public static function setUpBeforeClass()
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
    public function setUp()
    {
        foreach(glob($this->PROXY_CACHE_PATH . '/*') as $file)
        {
            unlink($file);
        }
    }

    /**
     * Shuts down the proxy server
     *
     * @todo This can probably be moved to the parent class
     */
    public static function tearDownAfterClass()
    {
        $client = new Client(self::URL_PROXY);
        $client->fetch('SHUTDOWN');
    }
}
