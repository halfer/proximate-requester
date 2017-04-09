Proximate/Requester
===

Introduction
---

Proximate/Requester is a PHP library to provide the building blocks for a record/playback
HTTP proxy. Its features include:

* When configured as an HTTP proxy, Proximate records any plaintext HTTP request without any
modifications being required on the client (other than changing the proxy settings).
* Where a request is regarded as matching a prior visit, the headers and response are played
back from the proxy cache, instead of being re-fetched.
* Where an HTTPS endpoint is to be recorded, a proxy cannot intercept this data in order to
record it. To handle this, the library offers a facility to convert the URL to HTTP at the
client, and a special header is injected to tell the proxy to switch back to HTTPS for the
fetch. The class handles this transparently for all endpoints regardless of protocol.
* For applications based on Guzzle 6, a middleware class is offered to transparently
convert HTTPS endpoints into interceptable ones before they hit the proxy. To show how this
works, integration classes for a third-party [web scraping robot](https://github.com/spatie/crawler)
are included.
* The storage system is based on the [PSR-6 caching standard](http://www.php-fig.org/psr/psr-6/),
which means that [any compatible cache provider](https://github.com/php-cache/cache/tree/master/src/Adapter)
could be used. Presently just a
[file cache](https://github.com/php-cache/cache/tree/master/src/Adapter/Filesystem) is implemented.
* Logging is based on the [PSR-3 standard](http://www.php-fig.org/psr/psr-3/).

Rationale
---

This project was written as a test system for a web scraping system, so that scrapers can be
written and tested on real, but non-live, websites. Using this library, one can provide a
start URL and a URL regex to match, and fetch matching pages via the proxy/recorder.

The proxy component originally used WireMock, a Java-based recorder. This comes with an
HTTP API to query the cache too. However, I soon discovered that the customisations required
for HTTPS handling would have required more Java-fu and time than was available. Given that there
were other things I did not like about this system, I decided to drop it in favour of a custom,
PHP-based approach.

There is very little provision in the PHP ecosystem for HTTP proxies, though it looks like
[something based on ReactPHP is coming](https://github.com/clue/php-http-proxy-react/issues/4). In
the meantime, I wrote my own, but I'd be very happy to switch to a better library that provides
the necessary internal hooks.

Server usage
---

There is a proxy server script in `console/proxy-server.php`, which starts up on a local port.
In practice, you'll want to use this approach in your own code, so you can configure it correctly:

    /**
     * Listens to localhost on port 8080
     * Uses a file-based cache
     * Stores cache data in /var/proximate/mycache/*
     */
    $proxier = new FileProxy('localhost:8080', '/var/proximate');
    $proxier->
        setup('mycache')->
        getProxy()->
        listenLoop();

To create more complex proxy devices, look at `src/Proximate/Proxy/FileProxy` to see how the
various classes are set up. You will need:

* A `Socket\Raw\Socket` for listening
* A PSR-6 compatible cache adapter, e.g. `Cache\Adapter\Filesystem\FilesystemCachePool`
* A child of `Proximate\CacheAdapter\BaseAdapter` for Proximate-specific cache operations
* An instance (or a child of) `Proximate\Proxy` to do the listening

Optionally you can add:

* A PSR-3 compatible logger

Client usage
---

See the examples in `console/simple-fetch.php` and `console/recursive-fetch.php`. The effect
of a local proxy is apparent with the recursive fetcher: on a slow connection where ~ 20 items
are fetched in as many seconds, running it a second time usually results in a run time of
around 0.3 sec!

Status
---

The library is working at present and unit tests are in the process of being written. Integration
tests will follow, as will a build process on Travis CI. If there is community interest, the
library will be published to Packagist.

There is no stable release as yet.

There is no license yet, but it will be F/OSS friendly.

Possible future enhancements
---

* Proxy entries are stored in the cache with an infinite expiry time. Some work could be added
to specify when items should automatically expire.
* Add a database cache adapters, e.g. for the Doctrine ORM.
* Allow cache adapters to declare their functionality, e.g. whether the pagination device
supports column sorting (the file cache does not, but the database one would do).
* The fetcher currently uses raw cURL, but this will probably be swapped to
[a wrapper library](https://github.com/php-mod/curl) instead, to improve testability.
* Support for older versions of Guzzle.

Related packages
---

Requester is designed to sit alongside other packages I have planned. These are all implemented
as Docker applications, so that any part can be swapped out as required:

* Proximate/Proxy - an implementation of the proxy class, using the file cache, sitting on a
Docker host volume. This is pretty much written already.
* Proximate/API - an HTTP API that uses the Requester library to offer a queryable interface
to the proxy's cache contents. Items can be retrieved in paginated form, individually, or deleted.
New URLs can be added to a crawl queue too.
* Proximate/App - a simple web app that talks to the API to request scrapes, to browse and delete
the proxy contents.
