<?php

/* 
 * Class to fetch a page via a Proximate proxy
 */

namespace Proximate\Tests\Integration;

use Proximate\Tests\Integration\TestCase;
use Proximate\Client;

class ClientTest extends TestCase
{
    /**
     * @dataProvider methodDataProvider
     */
    public function testMethods($method)
    {
        $body = $this->
            getClient()->
            setUrl($this->getWebServerUrl() . '/method.php')->
            fetch($method)->
            getResponseBody();
        $this->assertEquals($method, $body);
    }

    public function methodDataProvider()
    {
        return [
            ['GET'],
            ['POST'],
        ];
    }

    public function testPostVariables()
    {
        $vars = ['a' => 'b', 'c' => 'd', ];
        $body = $this->
            getClient()->
            setUrl($this->getWebServerUrl() . '/post-vars.php')->
            fetch('POST', $vars)->
            getResponseBody();
        $this->assertEquals(json_encode($vars), $body);
    }

    protected function getClient()
    {
        $client = new Client(null);

        return $client;
    }
}
