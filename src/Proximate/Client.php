<?php

/**
 * Class to proxy HTTP/HTTPS endpoints via an HTTP proxy so they can be recorded
 *
 * @todo Add a thin wrapper around curl to increase testability, or just use Guzzle!
 */

namespace Proximate;

use Proximate\Exception\Server as ServerException;

class Client
{
    use \Proximate\Feature\Curl;

    const REAL_URL_HEADER_NAME = 'X-Real-Url';

    protected $proxyUrl;
    protected $realUrlHeaderName = self::REAL_URL_HEADER_NAME;
    protected $url;
    protected $requestHeaders = [];
    protected $responseHeaders;
    protected $responseBody;

    /**
     * Creates a Requester object for the given proxy target
     *
     * @param string $proxyUrl
     */
    public function __construct($proxyUrl)
    {
        $this->proxyUrl = $proxyUrl;
    }

    /**
     * Specifies the HTTP or HTTPS address to be requested
     *
     * @param string $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Specifies any HTTP headers to send with the request
     *
     * @param array $requestHeaders
     * @return $this
     */
    public function setRequestHeaders(array $requestHeaders)
    {
        $this->requestHeaders = $requestHeaders;

        return $this;
    }

    /**
     * Customises the header to identify that the site is really HTTPS
     *
     * @param string $realUrlHeaderName
     */
    public function setRealUrlHeaderName($realUrlHeaderName)
    {
        $this->realUrlHeaderName = $realUrlHeaderName;

        return $this;
    }

    /**
     * Gets the URL to be requested (all URLs will be forced to HTTP)
     *
     * @return string
     */
    public function getUrl()
    {
        $url = preg_replace('#^https:#', 'http:', $this->url);

        return $url;
    }

    /**
     * Gets the request headers to be sent (adds in the real-url header too)
     *
     * @return array
     */
    public function getRequestHeaders()
    {
        $headers = $this->requestHeaders;
        if ($this->getUrl() !== $this->url)
        {
            $headers[] = self::REAL_URL_HEADER_NAME . ': ' . $this->url;
        }

        return $headers;
    }

    /**
     * Performs a fetch using cURL
     *
     * @todo Is it worth being more lenient about \r\n - maybe use preg_split()?
     */
    public function fetch($method = 'GET')
    {
        // Reset to empty
        $this->responseBody = null;
        $this->responseHeaders = null;

        // Choose a target URL
        $proxy = true;
        $url = $this->getUrl();
        if ($method == 'SHUTDOWN')
        {
            $proxy = false;
            $url = $this->proxyUrl;
        }

        // Do a curl fetch
        $curl = curl_init($url);
        curl_setopt_array(
            $curl,
            $this->getCurlOptsCustom($method, $proxy)
        );
        $response = curl_exec($curl);
        if ($response !== false)
        {
            $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
            $this->responseHeaders = substr($response, 0, $headerSize);
            $this->responseBody = substr($response, $headerSize);
        }
        curl_close($curl);
    }

    /**
     * Gets an array of curl options
     *
     * @return array
     */
    protected function getCurlOptsCustom($method, $proxy)
    {
        $extraOpts = [
            CURLOPT_HTTPHEADER => $this->getRequestHeaders(),
            CURLOPT_FOLLOWLOCATION => 1,
        ];
        if ($proxy)
        {
            $extraOpts[CURLOPT_PROXY] = $this->proxyUrl;
        }

        return $this->getCurlOpts($method) + $extraOpts;
    }

    /**
     * Returns response body or throws exception if not set
     */
    public function getResponseBody()
    {
        if (is_null($this->responseBody))
        {
            throw new ServerException(
                "A successful response body is not available"
            );
        }

        return $this->responseBody;
    }

    /**
     * Returns response headers or throws exception if not set
     */
    public function getResponseHeaders()
    {
        if (is_null($this->responseHeaders))
        {
            throw new ServerException(
                "A successful response header array is not available"
            );
        }

        $headers = explode("\r\n", trim($this->responseHeaders));

        return $headers;
    }
}
