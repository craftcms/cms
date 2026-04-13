<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void seedRegistered(array $bundleIds, array $jsFiles)
 * @method static void register(string $bundleId)
 * @method static string url(string $bundleId, string $path = '')
 * @method static array registeredBundleIds()
 * @method static array registeredJsFiles()
 *
 * @see \CraftCms\Cms\View\InternalAssets
 */
class InternalAssets extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\View\InternalAssets::class;
    }
}
