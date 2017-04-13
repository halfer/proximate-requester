<?php

/**
 * Response analysis and manipulation functions
 *
 * @todo Rename the $output parameters as $response here?
 */

namespace Proximate\Feature;

trait ResponseParser
{
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

    protected function addHeaders($response, array $additionalHeaders)
    {
        return
            $this->implodeHeaders(
                array_merge(
                    $this->getHeaders($response),
                    $additionalHeaders
                )
            ) .
            "\r\n" .
            $this->getBody($response);
    }
}
