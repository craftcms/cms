<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\HttpCache\ResponseTagger;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tags eligible responses with the elements they show.
 *
 * Global middleware, so it sees the final response. For a GET or HEAD request outside the control panel and actions,
 * it collects element tags and the earliest element expiry for the whole request. If the response succeeds, it adds
 * the element tags to the {@see ResponseTagger}, caps the response's lifetimes at that expiry, and writes the tag
 * header. Whether the response is stored is still up to the cache, from its headers.
 *
 * It does this after the rest of the request returns, so a cache driver that reads the tags or the capped lifetime
 * runs outside it: global middleware added with `prependMiddleware()`, or a `RequestHandled` listener. A driver that
 * sets a default lifetime sets it as `s-maxage` from middleware that runs inside it, such as global middleware added
 * with `pushMiddleware()`, so the cap applies to the default too.
 *
 * @since 6.0.0
 */
readonly class TagResponse
{
    public function __construct(
        private ElementCaches $elementCaches,
        private ResponseTagger $tagger,
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
                [$dependency, $ttl] = $this->elementCaches->stopCollectingCacheInfo();
            }
        }

        if (! $response->isSuccessful()) {
            return $response;
        }

        $this->tagger->addTags(array_values($dependency->tags ?? []));

        if (isset($ttl)) {
            $this->capLifetime($response, $ttl);
        }

        $this->tagger->tagResponse($response);

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
