<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\HttpCache\HttpCacheManager;
use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \FOS\HttpCache\ProxyClient\ProxyClient client(?string $name = null)
 * @method static \FOS\HttpCache\CacheInvalidator invalidator(?string $name = null)
 * @method static HttpCacheManager extend(string $driver, \Closure $callback)
 *
 * @see HttpCacheManager
 * @since 6.0.0
 */
class HttpCache extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return HttpCacheManager::class;
    }
}
