<?php

/**
 * This class injects a piece of middleware into a Guzzle 6 client in order to make HTTPS
 * requests "proxyable" via the Proximate proxy server. It switches the protocol to HTTP,
 * and injects the HTTPS address in a HTTP header.
 */

namespace Proximate\Guzzle;

use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Proximate\Proxy;

class ProxyMiddleware
{
    use \Proximate\Logger;

    /**
     * Returns Guzzle middleware to redirect proxied HTTPS requests to plaintext endpoints
     *
     * @return callable
     */
    public function getMiddleware()
    {
        return Middleware::mapRequest(function (RequestInterface $request) {

            // Special rules for HTTPS sites
            $uri = $request->getUri();
            if ($uri->getScheme() == 'https')
            {
                $newUri = $uri->withScheme('http');
                $this->log(
                    sprintf("Detected HTTPS site: %s, new URL is: %s", $uri, $newUri)
                );
                $request = $request->
                    withUri($newUri)->
                    withHeader(Proxy::REAL_URL_HEADER_NAME, (string) $uri);
            }

            return $request;
        });
    }
}
