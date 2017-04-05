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

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Spatie\Crawler\Crawler;
use Spatie\Crawler\Url;
use Spatie\Crawler\CrawlObserver;
use Spatie\Crawler\CrawlProfile;

require 'vendor/autoload.php';

$url =
$baseUrl =
    'http://ilovephp.jondh.me.uk/';

// @todo We need to add a Guzzle plugin into the client, to make curl/header changes
$client = new Client([
    RequestOptions::COOKIES => true,
    RequestOptions::CONNECT_TIMEOUT => 10,
    RequestOptions::TIMEOUT => 10,
    RequestOptions::ALLOW_REDIRECTS => true,
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

// @todo Add regex crawl logic here (in shouldCrawl())
class MyCrawlProfile implements CrawlProfile
{
    protected $baseUrl;

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function shouldCrawl(Url $url) : bool
    {
        // @todo This needs to be generalised
        $matchesRegex = strpos($url->path(), '/en/tutorial') === 0;
        $matchesRoot = $url->path() === '/';

        $shouldCrawl =
            $this->sameHost($url) &&
            ($matchesRegex || $matchesRoot);

        if ($shouldCrawl)
        {
            #echo sprintf("Should crawl %s\n", $url->path());
        }

        return $shouldCrawl;
    }

    protected function sameHost(Url $url)
    {
        return parse_url($this->baseUrl, PHP_URL_HOST) === $url->host;
    }
}

$t = microtime(true);
$crawler = new Crawler($client, 1);
$crawler->
    setCrawlProfile(new MyCrawlProfile($baseUrl))->
    setCrawlObserver(new MyCrawlObserver())->
    startCrawling($url);
$et = microtime(true) - $t;
echo sprintf("The crawl took %s sec\n", round($et, 1));
