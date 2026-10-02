<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string config(string $path = '')
 * @method static string projectConfigFile()
 * @method static string projectConfig(string $path = '', bool $create = true)
 * @method static string storage(string $path = '', bool $create = true)
 * @method static string tests(string $path = '')
 * @method static string composerBackups(string $path = '', bool $create = true)
 * @method static string configBackup(string $path = '', bool $create = true)
 * @method static string configDelta(string $path = '', bool $create = true)
 * @method static string vendor(string $path = '')
 * @method static string runtime(string $path = '', bool $create = true)
 * @method static string dbBackup(string $path = '', bool $create = true)
 * @method static string temp(string $path = '', bool $create = true)
 * @method static string assets(string $path = '', bool $create = true)
 * @method static string tempAssetUploads(string $path = '', bool $create = true)
 * @method static string assetSources(string $path = '', bool $create = true)
 * @method static string imageEditorSources(string $path = '', bool $create = true)
 * @method static string assetsIcons(string $path = '', bool $create = true)
 * @method static string imageTransforms(string $path = '', bool $create = true)
 * @method static string pluginIcons(string $path = '', bool $create = true)
 * @method static string logs(string $path = '', bool $create = true)
 * @method static string cpTranslations(string $path = '')
 * @method static string siteTranslations(string $path = '')
 * @method static string cpTemplates(string $path = '')
 * @method static string siteTemplates(string $path = '')
 * @method static string compiledClasses(string $path = '', bool $create = true)
 * @method static string compiledTemplates(string $path = '', bool $create = true)
 * @method static string sessions(string $path = '', bool $create = true)
 * @method static string cache(string $path = '', bool $create = true)
 * @method static string licenseKey()
 * @method static string[] system()
 * @method static bool ensurePathIsContained(string $path)
 *
 * @see \CraftCms\Cms\Support\Path
 */
class Path extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Support\Path::class;
    }
}
