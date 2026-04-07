<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static bool isCollectingCacheInfo()
 * @method static void startCollectingCacheInfo()
 * @method static void collectCacheTags(array<string> $tags)
 * @method static void setCacheExpiryDate(\DateTime $expiryDate)
 * @method static void collectCacheInfoForElement(\craft\base\ElementInterface $element)
 * @method static array stopCollectingCacheInfo()
 * @method static array<string> invalidateAll()
 * @method static array<string> invalidateForElementType(class-string<\craft\base\ElementInterface> $elementType)
 * @method static array<string> invalidateForElement(\craft\base\ElementInterface $element)
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
