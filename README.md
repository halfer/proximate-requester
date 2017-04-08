Proximate Requester
===

Introduction
---

Proximate Requester is a PHP library to provide the building blocks for a record/playback
HTTP proxy. Its features include:

* When configured as an HTTP proxy, Proximate records any plaintext HTTP request without any
modifications being required on the client (other than changing the proxy settings).
* Where a request is regarded as matching, the headers and response are played back from the
proxy instead of being fetched from the real endpoint.
* Where an HTTPS endpoint is to be recorded, a proxy cannot intercept this data in order to
record it, so the endpoint is converted to HTTP at the client, and a special header is injected
to tell the proxy to switch back to HTTPS for the fetch. A class is provided to do this
transparently for all HTTP/HTTPS endpoints.
* For applications based on Guzzle 6, a piece of middleware is offered to transparently
convert HTTPS endpoints into interceptable ones before they hit the proxy. To show how this
works, integration classes for a third-party [web scraping robot](https://github.com/spatie/crawler)
are included.
* The storage system is based on the [PSR-6 caching standard](http://www.php-fig.org/psr/psr-6/),
which means that [any compatible cache provider](https://github.com/php-cache/cache/tree/master/src/Adapter)
could be used. Presently just a
[file cache](https://github.com/php-cache/cache/tree/master/src/Adapter/Filesystem) is implemented.

Rationale
---

(@todo Why am I writing this? What did I consider instead?)

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

Related packages
---

(@todo What other packages do I plan to release?)