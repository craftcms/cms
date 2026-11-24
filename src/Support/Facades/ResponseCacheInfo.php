<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \CraftCms\Cms\HttpCache\ResponseCacheInfo addTags(string|list<string> $tags)
 * @method static list<string> getTags()
 * @method static \DateTimeImmutable|null getExpiryDate()
 *
 * @see \CraftCms\Cms\HttpCache\ResponseCacheInfo
 * @since 6.0.0
 */
class ResponseCacheInfo extends Facade
{
    /**
     * The instance is scoped to the request, so the facade mustn't keep the first one it resolves.
     */
    #[Override]
    protected static $cached = false;

    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\HttpCache\ResponseCacheInfo::class;
    }
}
