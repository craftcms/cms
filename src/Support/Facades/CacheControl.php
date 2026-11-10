<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \CraftCms\Cms\Http\CacheControl setMaxAge(int $value, ?string $reason = null)
 * @method static \CraftCms\Cms\Http\CacheControl setDuration(int $value, ?string $reason = null)
 * @method static \CraftCms\Cms\Http\CacheControl setSharedMaxAge(int $value, ?string $reason = null)
 * @method static \CraftCms\Cms\Http\CacheControl setStaleWhileRevalidate(int $value, ?string $reason = null)
 * @method static \CraftCms\Cms\Http\CacheControl setStaleIfError(int $value, ?string $reason = null)
 * @method static \CraftCms\Cms\Http\CacheControl setPublic(?string $reason = null)
 * @method static \CraftCms\Cms\Http\CacheControl setPrivate(?string $reason = null)
 * @method static \CraftCms\Cms\Http\CacheControl setNoCache(?string $reason = null)
 * @method static \CraftCms\Cms\Http\CacheControl setNoStore(?string $reason = null)
 *
 * @see \CraftCms\Cms\Http\CacheControl
 */
class CacheControl extends Facade
{
    #[Override]
    protected static $cached = false;

    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Http\CacheControl::class;
    }
}
