<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Http\ResponseCacheInfo;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Collects the elements eligible responses depend on, and caps their lifetimes at the earliest element expiry.
 *
 * Global middleware, so it sees the final response. For a GET or HEAD request outside the control panel and actions,
 * it collects element tags and the earliest element expiry for the whole request. They reach the {@see ResponseCacheInfo}
 * as they're collected, so cache integrations can read them from route middleware too. If the response succeeds, it
 * caps the response's `max-age`, `s-maxage` and `Expires` at that expiry. Whether the response is stored is still up
 * to the cache, from its headers.
 *
 * @since 6.0.0
 */
readonly class CollectCacheInfo
{
    public function __construct(
        private ElementCaches $elementCaches,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isEligibleRequest($request)) {
            return $next($request);
        }

        $this->elementCaches->startCollectingCacheInfo();

        try {
            $response = $next($request);
        } finally {
            if ($this->elementCaches->isCollectingCacheInfo()) {
                [, $ttl] = $this->elementCaches->stopCollectingCacheInfo();
            }
        }

        if ($response->isSuccessful() && isset($ttl)) {
            $this->capLifetime($response, $ttl);
        }

        return $response;
    }

    private function isEligibleRequest(Request $request): bool
    {
        return $request->isMethodCacheable() && ! $request->isCpRequest() && ! $request->isActionRequest();
    }

    /**
     * Caps the response's lifetimes at the earliest element expiry, so a page stops showing an expired entry on time.
     */
    private function capLifetime(Response $response, int $ttl): void
    {
        // Set the directives directly: `setSharedMaxAge()` also makes the response public.
        foreach (['s-maxage', 'max-age'] as $directive) {
            $value = $response->headers->getCacheControlDirective($directive);

            if (is_numeric($value) && (int) $value > $ttl) {
                $response->headers->addCacheControlDirective($directive, (string) $ttl);
            }
        }

        $expires = $response->getExpires();
        $cap = now()->addSeconds($ttl);

        if ($expires !== null && $expires > $cap) {
            $response->setExpires($cap);
        }
    }
}
