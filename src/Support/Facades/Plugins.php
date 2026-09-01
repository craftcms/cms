<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static void loadPlugins()
 * @method static bool arePluginsLoaded()
 * @method static \CraftCms\Cms\Plugin\Contracts\PluginInterface|null getPlugin(string $handle)
 * @method static \CraftCms\Cms\Plugin\Contracts\PluginInterface|null getPluginByPackageName(string $packageName)
 * @method static string|null getPluginHandleByClass(string $class)
 * @method static \CraftCms\Cms\Plugin\Contracts\PluginInterface[] getAllPlugins()
 * @method static void publishPluginAssets()
 * @method static bool enablePlugin(string $handle)
 * @method static bool disablePlugin(string $handle)
 * @method static bool installPlugin(string $handle, string|null $edition = null)
 * @method static bool uninstallPlugin(string $handle, bool $force = false)
 * @method static void switchEdition(string $handle, string $edition)
 * @method static bool savePluginSettings(\CraftCms\Cms\Plugin\Contracts\PluginInterface $plugin, array $settings)
 * @method static bool hasPluginVersionNumberChanged(\CraftCms\Cms\Plugin\Contracts\PluginInterface $plugin)
 * @method static bool isPluginUpdatePending(\CraftCms\Cms\Plugin\Contracts\PluginInterface $plugin)
 * @method static bool isPluginInstalled(string $handle)
 * @method static bool isPluginEnabled(string $handle)
 * @method static bool isPluginDisabled(string $handle)
 * @method static array|null getStoredPluginInfo(string $handle)
 * @method static void updatePluginVersionInfo(\CraftCms\Cms\Plugin\Contracts\PluginInterface $plugin)
 * @method static array|null getComposerPluginInfo(string|null $handle = null)
 * @method static \CraftCms\Cms\Plugin\Contracts\PluginInterface|null createPlugin(string $handle, array|null $info = null)
 * @method static \Illuminate\Support\Collection getAllPluginInfo()
 * @method static array getPluginInfo(string $handle)
 * @method static bool hasIssues(string $handle)
 * @method static string[] getLicenseIssues(string $handle)
 * @method static string getPluginIconSvg(string $handle)
 * @method static string|false|null getPluginLicenseKey(string $handle)
 * @method static bool setPluginLicenseKey(string $handle, string|null $licenseKey = null)
 * @method static string|null normalizePluginLicenseKey(string|null $licenseKey = null)
 * @method static \CraftCms\Cms\Shared\Enums\LicenseKeyStatus getPluginLicenseKeyStatus(string $handle)
 * @method static void addViteConfig(string $handle, array $config)
 * @method static void addStyle(string $handle, string $path)
 * @method static void addScript(string $handle, string $path)
 * @method static string getAssetsHtml()
 *
 * @see \CraftCms\Cms\Plugin\Plugins
 */
class Plugins extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Plugin\Plugins::class;
    }
}
