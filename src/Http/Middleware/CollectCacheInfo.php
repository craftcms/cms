<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Http\ResponseCacheInfo;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Collects the elements eligible responses depend on, and caps their lifetimes at the response's earliest expiry.
 *
 * Global middleware, so it sees the final response. For a GET or HEAD request outside the control panel and actions,
 * it collects element tags and the earliest element expiry for the whole request into {@see ResponseCacheInfo} as
 * they're collected, so cache integrations can read them from route middleware too. If the response succeeds or is a
 * `304 Not Modified`, it caps the response's `max-age`, `s-maxage` and `Expires` at
 * {@see ResponseCacheInfo::getExpiryDate()}. Whether the response is stored is still up to the cache, from its headers.
 *
 * @since 6.0.0
 */
readonly class CollectCacheInfo
{
    public function __construct(
        private ElementCaches $elementCaches,
        private ResponseCacheInfo $cacheInfo,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isEligibleRequest($request)) {
            return $next($request);
        }

        $this->cacheInfo->collectElements();
        $this->elementCaches->startCollectingCacheInfo();

        try {
            $response = $next($request);
        } finally {
            $this->elementCaches->stopCollectingCacheInfo();
            $this->cacheInfo->collectElements(false);
        }

        $expiryDate = $this->cacheInfo->getExpiryDate();

        // A 304 updates the stored response's freshness, so it's capped too.
        if ($expiryDate !== null && ($response->isSuccessful() || $response->getStatusCode() === 304)) {
            $this->capLifetime($response, max(0, $expiryDate->getTimestamp() - now()->getTimestamp()));
        }

        return $response;
    }

    private function isEligibleRequest(Request $request): bool
    {
        return $request->isMethodCacheable() && ! $request->isCpRequest() && ! $request->isActionRequest();
    }

    /**
     * Caps the response's lifetimes at its earliest expiry, so a page stops showing an expired entry on time.
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
