<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static bool isCollectingCacheInfo()
 * @method static void startCollectingCacheInfo()
 * @method static void collectCacheTags(array<array-key, mixed> $tags)
 * @method static void setCacheExpiryDate(\DateTimeInterface $expiryDate)
 * @method static void collectCacheInfoForElement(\CraftCms\Cms\Element\Contracts\ElementInterface $element)
 * @method static array<array-key, mixed> stopCollectingCacheInfo()
 * @method static array<array-key, mixed> invalidateAll()
 * @method static array<array-key, mixed> invalidateForElementType(string $elementType)
 * @method static array<array-key, mixed> invalidateForElement(\CraftCms\Cms\Element\Contracts\ElementInterface $element)
 *
 * @see \CraftCms\Cms\Element\ElementCaches
 */
class ElementCaches extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Element\ElementCaches::class;
    }
}
