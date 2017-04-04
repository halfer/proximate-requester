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

use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlObserver;
use Spatie\Crawler\CrawlInternalUrls;

$url =
$baseUrl =
    'http://ilovephp.jondh.me.uk/';

// @todo Instantiate Crawler manually to inject own Crawler, in order to inject a Guzzle
// plugin to make curl/header changes?
Crawler::create()->
    setCrawlProfile(new MyCrawlProfile($baseUrl))->
    setCrawlObserver(new MyCrawlObserver())->
    setConcurrency(1)->
    startCrawling($url);

class MyCrawlObserver implements CrawlObserver
{
    public function willCrawl(Url $url)
    {
    }

    public function hasBeenCrawled(Url $url, $response, Url $foundOnUrl = null)
    {
    }

    public function finishedCrawling()
    {
    }
}

// @todo Add regex crawl logic here (in shouldCrawl())
class MyCrawlProfile extends CrawlInternalUrls
{
}
