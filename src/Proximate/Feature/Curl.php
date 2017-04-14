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
    protected function getCurlOpts($method, array $vars = [])
    {
        $options = [];

        if ($method == 'GET')
        {
            // Nothing to add
        }
        elseif ($method == 'POST')
        {
            $options[CURLOPT_POSTFIELDS] = $vars;
        }
        else
        {
            $options[CURLOPT_CUSTOMREQUEST] = $method;
        }

        return [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 1,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_NOPROGRESS => 1,
            CURLOPT_VERBOSE => 0,
            CURLOPT_AUTOREFERER => 1,
        ] + $options;
    }
}
