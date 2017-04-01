<?php

/**
 * Code to kick off the proxy-recording of a HTTPS site
 */

require_once 'src/Proximate/Client.php';

$requester = new Proximate\Client('localhost:9001');

// Here's a plaintext version
#$url = 'http://ilovephp.jondh.me.uk/';
// Here's a HTTPS version
$url = 'https://blog.jondh.me.uk/';

$ok = $requester->
    setUrl($url)->
    fetch();
$headers = $requester->getResponseHeaders();
$body = $requester->getResponseBody();
