# Craft CMS

Craft is a content management system: it stores content as elements and renders them as site responses, which browsers and other HTTP caches may store.

## HTTP caching

Terms follow [RFC 9111](https://www.rfc-editor.org/rfc/rfc9111) and [RFC 9213](https://www.rfc-editor.org/rfc/rfc9213).

**Shared cache**:
A cache that stores responses for reuse by more than one user, such as a CDN or reverse proxy.
_Avoid_: CDN cache, proxy cache, edge cache, static cache

**Private cache**:
A cache dedicated to a single user, normally the browser.
_Avoid_: Browser cache, local cache

**Storable**:
Whether a cache is allowed to keep a response at all. A response can be storable by a private cache but not by a shared cache.
_Avoid_: Cacheable, shareable

**Freshness lifetime**:
How long a cache may reuse a stored response without checking with the origin.
_Avoid_: Duration, TTL, cache time

**Targeted cache-control field**:
A header carrying cache directives that only a named class of caches obeys, such as `CDN-Cache-Control`.
_Avoid_: CDN header, surrogate control

**Invalidation**:
Marking stored responses as no longer reusable, so caches fetch them again.
_Avoid_: Purge, clear, bust (except as a vendor's API verb)

**Cache tag**:
A label attached to a stored response so it can be invalidated together with every other response sharing that label. Not defined by an RFC.
_Avoid_: Surrogate key (Fastly), cache key
