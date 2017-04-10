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
 *
 * @todo If the proxy address is wrong, the script should emit an error
 * @todo Upgrade Spatie\Crawler from 1.3 to 2.1.x? (It looks like 1.3 does not take the
 * query string into account when differentiating URLs, see
 * https://github.com/spatie/crawler/issues/59).
 */

namespace Proximate;

// Namespaces for the injection of a Proximate tweaking device into Guzzle
use GuzzleHttp\HandlerStack;
use Proximate\Guzzle\ProxyMiddleware;

// Namespaces for the creation of a Guzzle client
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

// Namespaces for the creation and set-up of the crawler
use Spatie\Crawler\Crawler;
use Proximate\SpatieCrawler\Observer;
use Proximate\SpatieCrawler\Profile;

// Namespaces for logging
use Monolog\Logger;
use Monolog\Handler\ErrorLogHandler;

$rootPath = realpath(__DIR__ . '/..');
require_once $rootPath . '/vendor/autoload.php';

#$url = $startUrl = 'http://ilovephp.jondh.me.uk/';
#$pathRegex = '#^/en/tutorial#';

$url = $startUrl = 'https://blog.jondh.me.uk/';
$pathRegex = '#^/category#';

// Here is the optional logger to inject into the Guzzle middleware
$logger = new Logger('stdout');
$logger->pushHandler(new ErrorLogHandler());

$stack = HandlerStack::create();
$proxyMiddleware = new ProxyMiddleware();
$proxyMiddleware->addLogger($logger);
$stack->push($proxyMiddleware->getMiddleware());

// Create the HTTP client
$client = new Client([
    RequestOptions::COOKIES => true,
    RequestOptions::CONNECT_TIMEOUT => 10,
    RequestOptions::TIMEOUT => 10,
    RequestOptions::ALLOW_REDIRECTS => true,
    RequestOptions::PROXY => 'localhost:8081',
    'handler' => $stack,
]);

// Set up classes for the crawler (logger is optional in both cases)
$crawlObserver = new Observer();
$crawlProfile = new Profile($startUrl, $pathRegex);
$crawlObserver->addLogger($logger);
#$crawlProfile->addLogger($logger); // Very verbose!

$t = microtime(true);
$crawler = new Crawler($client, 1);
$crawler->
    setCrawlProfile($crawlProfile)->
    setCrawlObserver($crawlObserver)->
    startCrawling($url);
$et = microtime(true) - $t;
echo sprintf("The crawl took %s sec\n", round($et, 1));
