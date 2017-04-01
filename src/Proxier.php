<?php

/**
 * Class to scan headers and determine whether to modify the required protocol
 */

namespace Proximate;

class Proxier
{
    const REAL_URL_HEADER_NAME = 'X-Real-Url';

    protected $server;
    protected $client;
    protected $writeBuffer;
    protected $realUrlHeaderName = self::REAL_URL_HEADER_NAME;

    public function initialiseServerSocket($ip, $port)
    {
        foreach (['sockets', ] as $module)
        {
            if (!extension_loaded($module))
            {
                die(
                    sprintf("Error: extension `%s` not loaded\n", $module)
                );
            }
        }

        $this->server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->server, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($this->server, $ip, $port) or die('Could not bind to address');
        socket_listen($this->server);

        return $this;
    }

    /**
     * Default HTTP listening loop
     *
     * @todo Need to test to ensure $this->server is initialised before starting loop
     */
    public function listenLoop()
    {
        while($this->client = socket_accept($this->server))
        {
            // The buffer size should be enough to accommodate the request - 4K should be fine
            $input = socket_read($this->client, 1024 * 4);

            $match = null;
            if(preg_match("'^CONNECT ([^ ]+):(\d+) '", $input, $match)) // HTTPS
            {
                $this->handleHttpsConnect($input);
            }
            else
            {
                $this->handleHttpConnect($input);
            }

            socket_close($this->client);
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
        echo "HTTPS proxying not supported\n";

        $targetSiteData = "HTTP/1.1 500 Server error\r\n\r\n";
        socket_write($this->client, $targetSiteData);
    }

    /**
     * We've received an HTTP connection
     *
     * @param string $input
     */
    protected function handleHttpConnect($input)
    {
        $url = $this->getTargetUrlFromProxyRequest($input);
        $method = $this->getMethodFromProxyRequest($input);
        echo "URL is $url, method is $method\n";

        // Swap to the real URL if it is provided
        if ($realUrl = $this->checkRealUrlHeader($input))
        {
            $url = $realUrl;
            echo "Real URL detected: $url\n";
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
            #if (strpos($targetSiteData, 'HTTP/1.1 200 OK') !== false) {
            #    echo $targetSiteData . "\n";
            #}

            // Create a cache key and save the page data
            #savePage(createCacheKey($url, $method), $targetSiteData);
        }
        else
        {
            // Is there a better way to handle an error condition?
            $targetSiteData = "HTTP/1.1 500 Server error\r\n\r\n";
        }

        socket_write($this->client, $targetSiteData);
    }

    /**
     * Fetches the proxied site
     *
     * @todo Throw a custom exception on error, don't print the error out
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
            echo sprintf(
                "Fetch error: %s\n",
                curl_strerror(curl_errno($curl))
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

    function assembleOutput($headers, $body)
    {
        return $headers . "\r\n" . $body;
    }
}
