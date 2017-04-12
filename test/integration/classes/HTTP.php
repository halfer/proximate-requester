<?php

/**
 * Overrides the Request Factory in Spiderling, so proxy details can  be set
 */

namespace Proximate\Tests\Integration;

use Openbuildings\Spiderling\Driver_Simple_RequestFactory_HTTP;
use Openbuildings\Spiderling\Exception_Curl;

class HTTP extends Driver_Simple_RequestFactory_HTTP
{
    protected $proxyAddress;

    /**
     * Perform the request, follow redirects, return the response
     * @param  string $method
     * @param  string $url
     * @param  array $post
     * @return string
     */
    public function execute($method, $url, array $post = array())
    {
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_USERAGENT, $this->user_agent());

        if ($this->proxyAddress)
        {
            curl_setopt($curl, CURLOPT_PROXY, $this->proxyAddress);
        }

        if ($post)
        {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        }

        $response = curl_exec($curl);

        if ($response === FALSE OR curl_getinfo($curl, CURLINFO_HTTP_CODE) !== 200)
        {
            throw new Exception_Curl(
                'Curl: Download Error: :error, status :status on url :url',
                array(
                    ':url' => $url,
                    ':status' => curl_getinfo($curl, CURLINFO_HTTP_CODE),
                    ':error' => curl_error($curl)
                )
            );
        }

        $this->_current_url = urldecode(curl_getinfo($curl, CURLINFO_EFFECTIVE_URL));

        return $response;
    }

    public function setProxyAddress($proxyAddress)
    {
        $this->proxyAddress = $proxyAddress;
    }
}
