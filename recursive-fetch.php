<?php

/**
 * Working on an experimental version of `wget --recursive` that operates like the
 * system in simple-fetch.php. That it is to say that http addresses that match a regex
 * are transparently proxied to a recorder, and https addresses that match a regex are
 * converted to http, have a special header injected, then are transparently proxied to the
 * same recorder.
 *
 * While we could do the saving locally, it is nice to proxy it, as that creates a good
 * level of separation between functions. The alternative is to build an API to call to
 * store a response against a specific URL.
 */

namespace Proximate;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

use Spatie\Crawler\Crawler;
use Proximate\SpatieCrawler\Observer;
use Proximate\SpatieCrawler\Profile;

require 'vendor/autoload.php';

#$url = $startUrl = 'http://ilovephp.jondh.me.uk/';
#$pathRegex = '#^/en/tutorial#';

$url = $startUrl = 'https://blog.jondh.me.uk/';
$pathRegex = '#^/category#';

$stack = HandlerStack::create();
$stack->push(
    Middleware::mapRequest(function (RequestInterface $request) {
        // Special rules for HTTPS sites
        $uri = $request->getUri();
        if ($uri->getScheme() == 'https')
        {
            echo sprintf("Detected HTTPS site: %s\n", $uri);
            $newUri = $uri->withScheme('http');
            echo sprintf("New URL: %s\n", $newUri);
            $request = $request->
                withUri($newUri)->
                withHeader(Proxy::REAL_URL_HEADER_NAME, (string) $uri);
        }

        return $request;
    })
);

// Create the HTTP client
$client = new Client([
    RequestOptions::COOKIES => true,
    RequestOptions::CONNECT_TIMEOUT => 10,
    RequestOptions::TIMEOUT => 10,
    RequestOptions::ALLOW_REDIRECTS => true,
    RequestOptions::PROXY => 'localhost:9001',
    'handler' => $stack,
]);

$t = microtime(true);
$crawler = new Crawler($client, 1);
$crawler->
    setCrawlProfile(new Profile($startUrl, $pathRegex))->
    setCrawlObserver(new Observer())->
    startCrawling($url);
$et = microtime(true) - $t;
echo sprintf("The crawl took %s sec\n", round($et, 1));
