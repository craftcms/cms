<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CraftCms\Cms\View\AssetRegistry
 */
final class AssetRegistry extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\View\AssetRegistry::class;
    }
}
