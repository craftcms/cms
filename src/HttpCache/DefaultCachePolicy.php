<?php

declare(strict_types=1);

namespace CraftCms\Cms\HttpCache;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The standard HTTP caching rules.
 *
 * Every GET and HEAD request may be served from the cache, cookies or not, as Symfony's `HttpCache`, Fastly,
 * and Cloudflare do by default. Personal responses are kept out when storing instead: Craft sends no-cache
 * headers for them, and responses that set cookies are never stored.
 *
 * Symfony adds `private` to any response that doesn't say `public` or `s-maxage`, so `Response::isCacheable()`
 * also covers RFC 9111's rule for requests with `Authorization`.
 *
 * @since 6.0.0
 */
class DefaultCachePolicy implements CachePolicy
{
    public function isRequestCacheable(Request $request): bool
    {
        return $request->isMethodCacheable();
    }

    public function isResponseCacheable(Request $request, Response $response): bool
    {
        if (! $this->isRequestCacheable($request) || ! $response->isCacheable()) {
            return false;
        }

        // A stored `Set-Cookie` would hand one visitor's cookie to everyone.
        return $response->headers->getCookies() === [];
    }
}
