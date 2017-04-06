<?php

/**
 * A class for a Spatie\Crawler to guide the web crawler's operation
 */

namespace Proximate\SpatieCrawler;

use Spatie\Crawler\CrawlProfile;
use Spatie\Crawler\Url;

class Profile implements CrawlProfile
{
    use \Proximate\Logger;

    protected $startUrl;
    protected $pathRegex;

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

        if ($shouldCrawl)
        {
            $this->log(
                sprintf("Should crawl %s\n", $url)
            );
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
