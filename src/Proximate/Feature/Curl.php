<?php

/**
 * A trait for common cURL methods
 */

namespace Proximate\Feature;

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
            $options[CURLOPT_POST] = 1;
        }
        else
        {
            $options[CURLOPT_CUSTOMREQUEST] = $method;
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
