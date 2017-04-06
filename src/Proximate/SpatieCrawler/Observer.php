<?php

/**
 * A class for a Spatie\Crawler to observe the web crawler's operation
 */

namespace Proximate\SpatieCrawler;

use Spatie\Crawler\CrawlObserver;
use Spatie\Crawler\Url;

class Observer implements CrawlObserver
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
