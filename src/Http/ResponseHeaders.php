<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http;

use Closure;
use CraftCms\Cms\Http\Middleware\SetHeaders;
use Illuminate\Container\Attributes\Scoped;
use Symfony\Component\HttpFoundation\Response;

/**
 * Queues headers for the response to the current request, for code that runs before the response exists,
 * such as templates.
 *
 * Everything queued is applied in order by {@see SetHeaders}, so when two calls set the same header, the
 * last one wins.
 *
 * @since 6.0.0
 */
#[Scoped]
class ResponseHeaders
{
    /** @var list<Closure(Response): mixed> */
    private array $queue = [];

    private ?CacheControl $cacheControl = null;

    /**
     * Returns the response’s cache directives, set with the same methods as Symfony’s `Response`.
     *
     * ```php
     * ResponseHeaders::cacheControl()->setPublic()->setSharedMaxAge(86400)->setStaleWhileRevalidate(3600);
     * ```
     */
    public function cacheControl(): CacheControl
    {
        return $this->cacheControl ??= new CacheControl(function (Closure $callback): void {
            $this->queue[] = $callback;
        });
    }

    /**
     * Prevents browsers and shared caches from storing the response, replacing any cache headers queued before it.
     */
    public function noCache(): void
    {
        $this->queue[] = function (Response $response): void {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        };
    }

    /**
     * Lets browsers and shared caches store the response for the given number of seconds, replacing any cache
     * headers queued before it. A duration of 0 or less works like {@see noCache()}.
     */
    public function setCache(int $duration = 31536000): void
    {
        if ($duration <= 0) {
            $this->noCache();

            return;
        }

        $this->queue[] = function (Response $response) use ($duration): void {
            $response->headers->remove('Cache-Control');
            $response->headers->remove('Pragma');
            $response->headers->remove('Expires');
            $response->setPublic()->setMaxAge($duration);
        };
    }

    public function add(string $header, string $value, bool $replace = true): void
    {
        $this->queue[] = fn (Response $response) => $response->headers->set($header, $value, $replace);
    }

    /**
     * Applies everything queued to the response, in the order it was queued.
     */
    public function applyTo(Response $response): void
    {
        foreach ($this->queue as $callback) {
            $callback($response);
        }
    }

    /**
     * Forgets everything queued so far, such as when an exception replaces the response it was queued for.
     */
    public function reset(): void
    {
        $this->queue = [];
    }
}
