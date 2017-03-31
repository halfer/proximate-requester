<?php

/* 
 * Code to kick off the proxy-recording of a HTTPS site
 */

require_once 'src/Requester.php';

$requester = new Proximate\Requester('localhost:9001');

// Here's a plaintext version
#$url = 'http://ilovephp.jondh.me.uk/';
// @todo Try an HTTPS version next
$url = 'https://blog.jondh.me.uk/';

$ok = $requester->
    setUrl($url)->
    fetch();
$headers = $requester->getResponseHeaders();
$body = $requester->getResponseBody();
