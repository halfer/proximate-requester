<?php

/**
 * Response analysis and manipulation functions
 *
 * @todo Rename the $input parameters as $request here?
 */

namespace Proximate\Feature;

trait RequestParser
{
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
     * Gets the body section from the output
     *
     * @todo Copied from ResponseParser, can these be shared?
     *
     * @param string $request
     * @return string
     */
    protected function getBodyFromRequest($request)
    {
        $sections = explode("\r\n\r\n", $request, 2);
        $body = $sections[1];

        return $body;
    }

    protected function getPostVarsFromRequest($request)
    {
        $body = $this->getBodyFromRequest($request);
        $post = [];
        foreach (explode('&', $body) as $chunk)
        {
            $param = explode("=", $chunk);

            if (count($param) == 2)
            {
                $key = urldecode($param[0]);
                $value = urldecode($param[1]);
                $post[$key] = $value;
            }
        }

        return $post;
    }
}
