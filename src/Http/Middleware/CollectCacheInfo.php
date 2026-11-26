<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Http\CacheInfo;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Collects the cache info of eligible responses, and caps their lifetimes at the response's earliest expiry.
 *
 * Global middleware, so it sees the final response. For a GET or HEAD request outside the control panel and actions,
 * it collects element tags and the earliest element expiry into {@see CacheInfo} until the request
 * terminates, so cache integrations can read them from any middleware. If the response succeeds, redirects
 * or is a `304 Not Modified`, it caps the response's `max-age`, `s-maxage` and `Expires` at
 * {@see CacheInfo::getExpiryDate()}. Whether the response is stored is still up to the cache, from its headers.
 *
 * @since 6.0.0
 */
readonly class CollectCacheInfo
{
    public function __construct(
        private CacheInfo $cacheInfo,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isEligibleRequest($request)) {
            return $next($request);
        }

        $this->cacheInfo->start();
        $response = $next($request);

        $expiryDate = $this->cacheInfo->getExpiryDate();

        // Redirects can depend on elements too, and a 304 updates the stored response's freshness.
        if ($expiryDate !== null && ($response->isSuccessful() || $response->isRedirection())) {
            $this->capLifetime($response, $expiryDate);
        }

        return $response;
    }

    /**
     * Stops collecting once the response is sent, so outer middleware, such as a cache integration's, still reads it.
     */
    public function terminate(): void
    {
        $this->cacheInfo->stop();
    }

    private function isEligibleRequest(Request $request): bool
    {
        return $request->isMethodCacheable() && ! $request->isCpRequest() && ! $request->isActionRequest();
    }

    /**
     * Caps the response's lifetimes at its earliest expiry, so a page stops showing an expired entry on time.
     */
    private function capLifetime(Response $response, DateTimeImmutable $expiryDate): void
    {
        $ttl = max(0, $expiryDate->getTimestamp() - now()->getTimestamp());

        // Set the directives directly: `setSharedMaxAge()` also makes the response public.
        foreach (['s-maxage', 'max-age'] as $directive) {
            $value = $response->headers->getCacheControlDirective($directive);

            if (is_numeric($value) && (int) $value > $ttl) {
                $response->headers->addCacheControlDirective($directive, (string) $ttl);
            }
        }

        $expires = $response->getExpires();

        if ($expires !== null && $expires > $expiryDate) {
            $response->setExpires($expiryDate);
        }
    }
}
