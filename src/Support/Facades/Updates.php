<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isUpdateInfoCached()
 * @method static int totalAvailableUpdates(bool $check = false)
 * @method static bool isCriticalUpdateAvailable(bool $check = false)
 * @method static \CraftCms\Cms\Updates\Data\Updates getUpdates(bool $refresh = false)
 * @method static \CraftCms\Cms\Updates\Data\Updates cacheUpdates(\CraftCms\Cms\Updates\Data\Updates $updatesData)
 * @method static bool areMigrationsPending(bool $includeContent = false)
 * @method static string[] pendingMigrationHandles(bool $includeContent = false)
 * @method static void runMigrations(string[] $handles)
 * @method static bool isUpdatePending()
 * @method static bool isPluginUpdatePending()
 * @method static bool hasCraftVersionChanged()
 * @method static bool wasCraftBreakpointSkipped()
 * @method static bool isCraftSchemaVersionCompatible()
 * @method static bool isCraftUpdatePending()
 * @method static bool updateCraftVersionInfo()
 *
 * @see \CraftCms\Cms\Updates\Updates
 */
class Updates extends Facade
{
    #[\Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Updates\Updates::class;
    }
}
