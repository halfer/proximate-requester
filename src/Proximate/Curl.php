<?php

/**
 * A trait for common cURL methods
 */

namespace Proximate;

trait Curl
{
    /**
     * Gets an array of curl options
     *
     * @return array
     */
    protected function getCurlOpts($method)
    {
        $options = [];

        if ($method == 'GET')
        {
            // Nothing to add
        }
        elseif ($method == 'POST')
        {
            $options['CURLOPT_POST'] = 1;
        }
        else
        {
            // @todo Add custom method, see http://stackoverflow.com/q/13420952
            throw new Exception\Init("Custom HTTP method not currently supported");
        }

        return [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 1,
            CURLOPT_VERBOSE => 1,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_NOPROGRESS => 1,
            CURLOPT_VERBOSE => 0,
            CURLOPT_AUTOREFERER => 1,
        ] + $options;
    }
}
