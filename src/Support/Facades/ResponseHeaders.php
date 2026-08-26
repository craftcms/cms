<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static void noCache()
 * @method static void setCache(int $duration = 31536000, bool $replace = true)
 * @method static void add(string $header, string $value, bool $replace = true)
 *
 * @see \CraftCms\Cms\Http\ResponseHeaders
 */
class ResponseHeaders extends Facade
{
    #[Override]
    protected static $cached = false;

    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Http\ResponseHeaders::class;
    }
}
