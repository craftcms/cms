<?php

declare(strict_types=1);

namespace CraftCms\Cms\HttpCache;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Decides what a cache may serve and store.
 *
 * App-cache drivers call {@see isRequestCacheable()} before serving a hit and {@see isResponseCacheable()}
 * before storing. Edge drivers call {@see isResponseCacheable()} before adding their shared-cache header.
 * Bind your own implementation to change both rules, as they have to agree.
 *
 * @since 6.0.0
 */
interface CachePolicy
{
    /**
     * Whether this request may be answered from the cache.
     */
    public function isRequestCacheable(Request $request): bool;

    /**
     * Whether this finished response may be stored.
     */
    public function isResponseCacheable(Request $request, Response $response): bool;
}
