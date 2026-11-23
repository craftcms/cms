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
 * Binding your own implementation changes both rules, which have to agree, for every driver. A driver with stricter
 * rules of its own, such as an app cache that only stores HTML, uses its own class extending {@see DefaultCachePolicy}
 * rather than rebinding this one.
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
