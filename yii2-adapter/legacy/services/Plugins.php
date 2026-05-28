<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\events\PluginEvent;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use CraftCms\Cms\Plugin\Events\PluginDisabled;
use CraftCms\Cms\Plugin\Events\PluginDisabling;
use CraftCms\Cms\Plugin\Events\PluginEnabled;
use CraftCms\Cms\Plugin\Events\PluginEnabling;
use CraftCms\Cms\Plugin\Events\PluginInstalled;
use CraftCms\Cms\Plugin\Events\PluginInstalling;
use CraftCms\Cms\Plugin\Events\PluginRegistered;
use CraftCms\Cms\Plugin\Events\PluginSettingsSaved;
use CraftCms\Cms\Plugin\Events\PluginsLoading;
use CraftCms\Cms\Plugin\Events\PluginUninstalled;
use CraftCms\Cms\Plugin\Events\PluginUninstalling;
use CraftCms\Cms\Plugin\Events\PluginUnregistered;
use CraftCms\Cms\Plugin\Events\SavingPluginSettings;
use CraftCms\Cms\Plugin\Exceptions\InvalidLicenseKeyException;
use CraftCms\Cms\Plugin\Exceptions\InvalidPluginException;
use CraftCms\Cms\Plugin\Plugins as PluginsService;
use CraftCms\Cms\Shared\Enums\LicenseKeyStatus;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Throwable;
use yii\base\Component;
use yii\base\Module;

/**
 * The Plugins service provides APIs for managing plugins.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getPlugins()|`Craft::$app->getPlugins()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0. Use {@see \CraftCms\Cms\Plugin\Plugins} instead.
 */
class Plugins extends Component
{
    /**
     * @event \yii\base\Event The event that is triggered before any plugins have been loaded
     */
    public const EVENT_BEFORE_LOAD_PLUGINS = 'beforeLoadPlugins';

    /**
     * @event \yii\base\Event The event that is triggered after all plugins have been loaded
     */
    public const EVENT_AFTER_LOAD_PLUGINS = 'afterLoadPlugins';

    /**
     * @event PluginEvent The event that is triggered before a plugin is enabled
     */
    public const EVENT_BEFORE_ENABLE_PLUGIN = 'beforeEnablePlugin';
    /**
     * @event PluginEvent The event that is triggered after a plugin is enabled
     */
    public const EVENT_AFTER_ENABLE_PLUGIN = 'afterEnablePlugin';

    /**
     * @event PluginEvent The event that is triggered before a plugin is disabled
     */
    public const EVENT_BEFORE_DISABLE_PLUGIN = 'beforeDisablePlugin';
    /**
     * @event PluginEvent The event that is triggered after a plugin is disabled
     */
    public const EVENT_AFTER_DISABLE_PLUGIN = 'afterDisablePlugin';

    /**
     * @event PluginEvent The event that is triggered before a plugin is installed
     */
    public const EVENT_BEFORE_INSTALL_PLUGIN = 'beforeInstallPlugin';

    /**
     * @event PluginEvent The event that is triggered after a plugin is installed
     */
    public const EVENT_AFTER_INSTALL_PLUGIN = 'afterInstallPlugin';

    /**
     * @event PluginEvent The event that is triggered before a plugin is uninstalled
     */
    public const EVENT_BEFORE_UNINSTALL_PLUGIN = 'beforeUninstallPlugin';

    /**
     * @event PluginEvent The event that is triggered after a plugin is uninstalled
     */
    public const EVENT_AFTER_UNINSTALL_PLUGIN = 'afterUninstallPlugin';

    /**
     * @event PluginEvent The event that is triggered before a plugin’s settings are saved
     */
    public const EVENT_BEFORE_SAVE_PLUGIN_SETTINGS = 'beforeSavePluginSettings';

    /**
     * @event PluginEvent The event that is triggered after a plugin’s settings are saved
     */
    public const EVENT_AFTER_SAVE_PLUGIN_SETTINGS = 'afterSavePluginSettings';

    /**
     * Loads the enabled plugins.
     */
    public function loadPlugins(): void
    {
        app(PluginsService::class)->loadPlugins();
    }

    /**
     * Returns whether plugins have been loaded yet for this request.
     *
     * @return bool
     */
    public function arePluginsLoaded(): bool
    {
        return app(PluginsService::class)->arePluginsLoaded();
    }

    /**
     * Returns an enabled plugin by its handle.
     *
     * @param string $handle The plugin’s handle
     *
     * @return PluginInterface|null The plugin, or null if it doesn’t exist
     */
    public function getPlugin(string $handle): ?PluginInterface
    {
        return app(PluginsService::class)->getPlugin($handle);
    }

    /**
     * Returns an enabled plugin by its package name.
     *
     * @param string $packageName The plugin’s package name
     *
     * @return PluginInterface|null The plugin, or null if it doesn’t exist
     */
    public function getPluginByPackageName(string $packageName): ?PluginInterface
    {
        return app(PluginsService::class)->getPluginByPackageName($packageName);
    }

    /**
     * Returns the plugin handle that contains the given class, if any.
     *
     * The plugin may not actually be installed.
     *
     * @param class-string $class
     *
     * @return string|null The plugin handle, or null if it can’t be determined
     */
    public function getPluginHandleByClass(string $class): ?string
    {
        return app(PluginsService::class)->getPluginHandleByClass($class);
    }

    /**
     * Returns all the enabled plugins.
     *
     * @return PluginInterface[]
     */
    public function getAllPlugins(): array
    {
        return app(PluginsService::class)->getAllPlugins();
    }

    /**
     * Enables a plugin by its handle.
     *
     * @param string $handle The plugin’s handle
     *
     * @return bool Whether the plugin was enabled successfully
     * @throws InvalidPluginException if the plugin isn't installed
     */
    public function enablePlugin(string $handle): bool
    {
        return app(PluginsService::class)->enablePlugin($handle);
    }

    /**
     * Disables a plugin by its handle.
     *
     * @param string $handle The plugin’s handle
     *
     * @return bool Whether the plugin was disabled successfully
     * @throws InvalidPluginException if the plugin isn’t installed
     */
    public function disablePlugin(string $handle): bool
    {
        return app(PluginsService::class)->disablePlugin($handle);
    }

    /**
     * Installs a plugin by its handle.
     *
     * @param string $handle The plugin’s handle
     * @param string|null $edition The plugin’s edition
     *
     * @return bool Whether the plugin was installed successfully.
     * @throws InvalidPluginException if the plugin doesn’t exist
     * @throws Throwable if reasons
     */
    public function installPlugin(string $handle, ?string $edition = null): bool
    {
        return app(PluginsService::class)->installPlugin($handle, $edition);
    }

    /**
     * Uninstalls a plugin by its handle.
     *
     * @param string $handle The plugin’s handle
     * @param bool $force Whether to force the plugin uninstallation, even if it is disabled, its
     * `uninstall()` method returns `false`, or its files aren’t present
     *
     * @return bool Whether the plugin was uninstalled successfully
     * @throws InvalidPluginException if the plugin doesn’t exist
     * @throws Throwable if reasons
     */
    public function uninstallPlugin(string $handle, bool $force = false): bool
    {
        return app(PluginsService::class)->uninstallPlugin($handle, $force);
    }

    /**
     * Switches a plugin’s edition.
     *
     * @param string $handle The plugin’s handle
     * @param string $edition The plugin’s edition
     *
     * @throws InvalidPluginException if the plugin doesn’t exist
     * @throws InvalidArgumentException if $edition is invalid
     * @throws Throwable if reasons
     */
    public function switchEdition(string $handle, string $edition): void
    {
        app(PluginsService::class)->switchEdition($handle, $edition);
    }

    /**
     * Saves a plugin’s settings.
     *
     * @param PluginInterface $plugin The plugin
     * @param array $settings The plugin’s new settings
     *
     * @return bool Whether the plugin’s settings were saved successfully
     */
    public function savePluginSettings(PluginInterface $plugin, array $settings): bool
    {
        if (is_null($pluginSettings = $plugin->getSettings())) {
            return false;
        }

        /**
         * We override this as the legacy service needs
         * to save with setting safeOnly to `false`.
         * @var \craft\base\Model $pluginSettings
         */
        $pluginSettings->setAttributes($settings, false);

        return app(PluginsService::class)->savePluginSettings($plugin, $settings);
    }

    /**
     * Returns whether the given plugin’s version number has changed from what we have recorded in the database.
     *
     * @param PluginInterface $plugin The plugin
     *
     * @return bool Whether the plugin’s version number has changed from what we have recorded in the database
     */
    public function hasPluginVersionNumberChanged(PluginInterface $plugin): bool
    {
        return app(PluginsService::class)->hasPluginVersionNumberChanged($plugin);
    }

    /**
     * Returns whether the given plugin’s local schema version is greater than the record we have in the database.
     *
     * @param PluginInterface $plugin The plugin
     *
     * @return bool Whether the plugin’s local schema version is greater than the record we have in the database
     * @since 4.0.0
     */
    public function isPluginUpdatePending(PluginInterface $plugin): bool
    {
        return app(PluginsService::class)->isPluginUpdatePending($plugin);
    }

    /**
     * Returns whether a given plugin is installed (even if it's disabled).
     *
     * @param string $handle The plugin handle
     *
     * @return bool
     */
    public function isPluginInstalled(string $handle): bool
    {
        return app(PluginsService::class)->isPluginInstalled($handle);
    }

    /**
     * Returns whether a given plugin is installed and enabled.
     *
     * @param string $handle The plugin handle
     *
     * @return bool
     */
    public function isPluginEnabled(string $handle): bool
    {
        return app(PluginsService::class)->isPluginEnabled($handle);
    }

    /**
     * Returns whether a given plugin is installed but disabled.
     *
     * @param string $handle The plugin handle
     *
     * @return bool
     */
    public function isPluginDisabled(string $handle): bool
    {
        return app(PluginsService::class)->isPluginDisabled($handle);
    }

    /**
     * Returns the stored info for a given plugin.
     *
     * @param string $handle The plugin handle
     *
     * @return array|null The stored info, if there is any
     */
    public function getStoredPluginInfo(string $handle): ?array
    {
        return app(PluginsService::class)->getStoredPluginInfo($handle);
    }

    /**
     * Updates a plugin’s stored version & schema version to match what’s Composer-installed.
     *
     * @param PluginInterface $plugin
     *
     * @since 3.7.13
     */
    public function updatePluginVersionInfo(PluginInterface $plugin): void
    {
        app(PluginsService::class)->updatePluginVersionInfo($plugin);
    }

    /**
     * Returns the Composer-supplied info
     *
     * @param string|null $handle The plugin handle. If null is passed, info for all Composer-installed plugins will be returned.
     *
     * @return array|null The plugin info, or null if an unknown handle was passed.
     */
    public function getComposerPluginInfo(?string $handle = null): ?array
    {
        return app(PluginsService::class)->getComposerPluginInfo($handle);
    }

    /**
     * Creates and returns a new plugin instance based on its handle.
     *
     * @param string $handle The plugin’s handle
     * @param array|null $info The plugin’s stored info, if any
     *
     * @return PluginInterface|null
     * @throws InvalidPluginException if $handle is invalid
     */
    public function createPlugin(string $handle, ?array $info = null): ?PluginInterface
    {
        return app(PluginsService::class)->createPlugin($handle, $info);
    }

    /**
     * Returns info about all of the plugins we can find, whether they’re installed or not.
     *
     * @return array
     */
    public function getAllPluginInfo(): array
    {
        return app(PluginsService::class)->getAllPluginInfo()->all();
    }

    /**
     * Returns info about a plugin, whether it's installed or not.
     *
     * @param string $handle The plugin’s handle
     *
     * @return array
     * @throws InvalidPluginException if the plugin isn't Composer-installed
     */
    public function getPluginInfo(string $handle): array
    {
        return app(PluginsService::class)->getPluginInfo($handle);
    }

    /**
     * Returns whether a plugin has licensing issues.
     *
     * @param string $handle
     *
     * @return bool
     */
    public function hasIssues(string $handle): bool
    {
        return app(PluginsService::class)->hasIssues($handle);
    }

    /**
     * Returns any issues with a plugin license.
     *
     * The response will be an array containing a combination of these strings:
     *
     * - `wrong_edition` – if the current edition isn't the licensed one, and
     *   testing editions isn't allowed
     * - `mismatched` – if the license key is tied to a different Craft license
     * - `astray` – if the installed version is greater than the highest version
     *   the license is allowed to run
     * - `required` – if no license key is present but one is required
     * - `invalid` – if a license key is present but it’s invalid
     *
     * @param string $handle
     *
     * @return string[]
     */
    public function getLicenseIssues(string $handle): array
    {
        return app(PluginsService::class)->getLicenseIssues($handle);
    }

    /**
     * Returns a plugin’s SVG icon.
     *
     * @param string $handle The plugin’s handle
     *
     * @return string The given plugin’s SVG icon
     */
    public function getPluginIconSvg(string $handle): string
    {
        return app(PluginsService::class)->getPluginIconSvg($handle);
    }

    /**
     * Returns the license key stored for a given plugin, if it was purchased through the Store.
     *
     * @param string $handle The plugin’s handle
     *
     * @return string|null The plugin’s license key, or null if it isn’t known
     * @throws InvalidLicenseKeyException
     */
    public function getPluginLicenseKey(string $handle): ?string
    {
        return app(PluginsService::class)->getPluginLicenseKey($handle);
    }

    /**
     * Sets a plugin’s license key.
     *
     * Note this should *not* be used to store license keys generated by third party stores.
     *
     * @param string $handle The plugin’s handle
     * @param string|null $licenseKey The plugin’s license key
     *
     * @return bool Whether the license key was updated successfully
     * @throws InvalidPluginException if the plugin isn't installed
     * @throws InvalidLicenseKeyException if $licenseKey is invalid
     */
    public function setPluginLicenseKey(string $handle, ?string $licenseKey = null): bool
    {
        return app(PluginsService::class)->setPluginLicenseKey($handle, $licenseKey);
    }

    /**
     * Normalizes a plugin license key.
     *
     * @param string|null $licenseKey
     *
     * @return string|null
     * @throws InvalidLicenseKeyException
     */
    public function normalizePluginLicenseKey(?string $licenseKey = null): ?string
    {
        return app(PluginsService::class)->normalizePluginLicenseKey($licenseKey);
    }

    /**
     * Returns the license key status of a given plugin.
     *
     * @param string $handle The plugin’s handle
     *
     * @return LicenseKeyStatus
     */
    public function getPluginLicenseKeyStatus(string $handle): LicenseKeyStatus
    {
        return app(PluginsService::class)->getPluginLicenseKeyStatus($handle);
    }

    public static function registerEvents(): void
    {
        $pluginService = Craft::$app->getPlugins();

        Event::listen(
            PluginsLoading::class,
            fn() => $pluginService->trigger(self::EVENT_BEFORE_LOAD_PLUGINS),
        );

        app()->booted(function() use ($pluginService) {
            $pluginService->trigger(self::EVENT_AFTER_LOAD_PLUGINS);
        });

        Event::listen(
            PluginEnabling::class,
            fn(PluginEnabling $event) => $pluginService->trigger(self::EVENT_BEFORE_ENABLE_PLUGIN, new PluginEvent([
                'plugin' => $event->plugin,
            ])),
        );

        Event::listen(
            PluginEnabled::class,
            fn(PluginEnabled $event) => $pluginService->trigger(self::EVENT_AFTER_ENABLE_PLUGIN, new PluginEvent([
                'plugin' => $event->plugin,
            ])),
        );

        Event::listen(
            PluginDisabling::class,
            fn(PluginDisabling $event) => $pluginService->trigger(self::EVENT_BEFORE_DISABLE_PLUGIN, new PluginEvent([
                'plugin' => $event->plugin,
            ])),
        );

        Event::listen(
            PluginDisabled::class,
            fn(PluginDisabled $event) => $pluginService->trigger(self::EVENT_AFTER_DISABLE_PLUGIN, new PluginEvent([
                'plugin' => $event->plugin,
            ])),
        );

        Event::listen(
            PluginInstalling::class,
            fn(PluginInstalling $event) => $pluginService->trigger(self::EVENT_BEFORE_INSTALL_PLUGIN, new PluginEvent([
                'plugin' => $event->plugin,
            ])),
        );

        Event::listen(
            PluginInstalled::class,
            fn(PluginInstalled $event) => $pluginService->trigger(self::EVENT_AFTER_INSTALL_PLUGIN, new PluginEvent([
                'plugin' => $event->plugin,
            ])),
        );

        Event::listen(
            PluginUninstalling::class,
            fn(PluginUninstalling $event) => $pluginService->trigger(self::EVENT_BEFORE_UNINSTALL_PLUGIN, new PluginEvent([
                'plugin' => $event->plugin,
            ])),
        );

        Event::listen(
            PluginUninstalled::class,
            fn(PluginUninstalled $event) => $pluginService->trigger(self::EVENT_AFTER_UNINSTALL_PLUGIN, new PluginEvent([
                'plugin' => $event->plugin,
            ])),
        );

        Event::listen(
            SavingPluginSettings::class,
            fn(SavingPluginSettings $event) => $pluginService->trigger(self::EVENT_BEFORE_SAVE_PLUGIN_SETTINGS, new PluginEvent([
                'plugin' => $event->plugin,
            ])),
        );

        Event::listen(
            PluginSettingsSaved::class,
            fn(PluginSettingsSaved $event) => $pluginService->trigger(self::EVENT_AFTER_SAVE_PLUGIN_SETTINGS, new PluginEvent([
                'plugin' => $event->plugin,
            ])),
        );

        Event::listen(
            PluginRegistered::class,
            function(PluginRegistered $event) {
                if (!$event->plugin instanceof Module) {
                    return;
                }

                Craft::$app->setModule($event->plugin->handle, $event->plugin);
            }
        );

        Event::listen(
            PluginUnregistered::class,
            function(PluginUnregistered $event) {
                if (!$event->plugin instanceof Module) {
                    return;
                }

                Craft::$app->setModule($event->plugin->handle, null);
            }
        );
    }
}
