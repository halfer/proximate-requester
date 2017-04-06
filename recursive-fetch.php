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
use Spatie\Crawler\Url;
use Spatie\Crawler\CrawlObserver;
use Spatie\Crawler\CrawlProfile;

require 'vendor/autoload.php';

$url =
$startUrl =
    'http://ilovephp.jondh.me.uk/';
$pathRegex = '#^/en/tutorial#';

$stack = HandlerStack::create();
$stack->push(
    Middleware::mapRequest(function (RequestInterface $request) {
        echo "Middleware running, woop\n";
        // Special rules for HTTPS sites
        $uri = $request->getUri();
        if ($uri->getScheme() == 'https')
        {
            echo "Detected HTTP site\n";
            $newScheme = $uri->withScheme('http');
            #$request = $request->
            #    withUri($uri)->
            #    withHeader(Proxy::REAL_URL_HEADER_NAME, (string) $newScheme);
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
    'handler' => $stack,
]);

class MyCrawlObserver implements CrawlObserver
{
    public function willCrawl(Url $url)
    {
    }

    public function hasBeenCrawled(Url $url, $response, Url $foundOnUrl = null)
    {
        echo sprintf("Crawled URL: %s\n", $url->path());
    }

    public function finishedCrawling()
    {
    }
}

class MyCrawlProfile implements CrawlProfile
{
    protected $startUrl;
    protected $pathRegex;
    protected $debug = false;

    /**
     * Sets up some filtering settings for the crawler
     *
     * @param string $startUrl e.g. "http://www.example.com"
     * @param string $pathRegex e.g. "#^/careers#"
     */
    public function __construct(string $startUrl, $pathRegex)
    {
        $this->startUrl = $startUrl;
        $this->pathRegex = $pathRegex;
    }

    public function shouldCrawl(Url $url) : bool
    {
        $matchesRegex = $this->regexMatch($url);
        $matchesRoot = $this->startMatch($url);

        $shouldCrawl =
            $this->sameHost($url) &&
            ($matchesRegex || $matchesRoot);

        if ($shouldCrawl && $this->debug)
        {
            echo sprintf("Should crawl %s\n", $url->path());
        }

        return $shouldCrawl;
    }

    protected function sameHost(Url $url)
    {
        return parse_url($this->startUrl, PHP_URL_HOST) === $url->host;
    }

    protected function startMatch(Url $url)
    {
        return ((string) $url) == $this->startUrl;
    }

    protected function regexMatch(Url $url)
    {
        return preg_match($this->pathRegex, $url->path) === 1;
    }
}

$t = microtime(true);
$crawler = new Crawler($client, 1);
$crawler->
    setCrawlProfile(new MyCrawlProfile($startUrl, $pathRegex))->
    setCrawlObserver(new MyCrawlObserver())->
    startCrawling($url);
$et = microtime(true) - $t;
echo sprintf("The crawl took %s sec\n", round($et, 1));
