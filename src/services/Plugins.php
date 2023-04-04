<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\Plugin;
use craft\base\PluginInterface;
use craft\db\MigrationManager;
use craft\db\Query;
use craft\db\Table;
use craft\enums\LicenseKeyStatus;
use craft\errors\InvalidLicenseKeyException;
use craft\errors\InvalidPluginException;
use craft\events\PluginEvent;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use DateTime;
use ReflectionClass;
use ReflectionException;
use Throwable;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\helpers\Inflector;
use yii\web\HttpException;

/**
 * The Plugins service provides APIs for managing plugins.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getPlugins()|`Craft::$app->plugins`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
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
     * @var array[] Custom plugin configurations.
     * @since 3.4.0
     */
    public array $pluginConfigs;

    /**
     * @var bool Whether plugins have been loaded yet for this request
     */
    private bool $_pluginsLoaded = false;

    /**
     * @var bool Whether plugins are in the middle of being loaded
     */
    private bool $_loadingPlugins = false;

    /**
     * @var PluginInterface[] All the enabled plugins, indexed by handles
     */
    private array $_plugins = [];

    /**
     * @var array|null Plugin info provided by Composer, indexed by handles
     */
    private ?array $_composerPluginInfo = null;

    /**
     * @var array All of the stored info for plugins (enabled or disabled), indexed by handles
     * @see getStoredPluginInfo()
     */
    private array $_storedPluginInfo;

    /**
     * @var string[]|string|null Any plugin handles that must be disabled per the `disablePlugins` config setting
     */
    private string|array|null $_forceDisabledPlugins = null;

    /**
     * @var string[] Cache for [[getPluginHandleByClass()]]
     */
    private array $_classPluginHandles = [];

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $this->_forceDisabledPlugins = is_array($generalConfig->disabledPlugins) ? array_flip($generalConfig->disabledPlugins) : $generalConfig->disabledPlugins;

        $this->_composerPluginInfo = [];

        $path = Craft::$app->getVendorPath() . DIRECTORY_SEPARATOR . 'craftcms' . DIRECTORY_SEPARATOR . 'plugins.php';

        if (file_exists($path)) {
            /** @var array $plugins */
            $plugins = require $path;

            foreach ($plugins as $packageName => $plugin) {
                $plugin['packageName'] = $packageName;
                // Normalize the base path (and find the actual path, not a possibly-symlinked path)
                if (isset($plugin['basePath'])) {
                    if (($basePath = realpath($plugin['basePath'])) !== false) {
                        $plugin['basePath'] = FileHelper::normalizePath($basePath);
                    } else {
                        Craft::warning("Invalid plugin base path: {$plugin['basePath']}", __METHOD__);
                        unset($plugin['basePath']);
                    }
                }
                $handle = $this->_normalizeHandle(ArrayHelper::remove($plugin, 'handle'));
                $this->_composerPluginInfo[$handle] = $plugin;
            }
        }
    }

    /**
     * Loads the enabled plugins.
     */
    public function loadPlugins(): void
    {
        if ($this->_pluginsLoaded === true || $this->_loadingPlugins === true || Craft::$app->getIsInstalled() === false) {
            return;
        }

        // Prevent this function from getting called twice.
        $this->_loadingPlugins = true;

        // Fire a 'beforeLoadPlugins' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_LOAD_PLUGINS)) {
            $this->trigger(self::EVENT_BEFORE_LOAD_PLUGINS);
        }

        // Find all of the installed plugins
        $pluginInfo = $this->_createPluginQuery()
            ->orderBy(['handle' => SORT_ASC])
            ->indexBy('handle')
            ->all();

        $this->_storedPluginInfo = [];

        foreach ($pluginInfo as $handle => $row) {
            try {
                $configData = $this->_getPluginConfigData($handle);
            } catch (InvalidPluginException) {
                continue;
            }

            // Clean up the row data
            $row['edition'] = $configData['edition'] ?? null;
            $row['settings'] = $configData['settings'] ?? [];
            $row['licenseKey'] = $configData['licenseKey'] ?? null;
            $row['enabled'] = !empty($configData['enabled']);
            $row['installDate'] = DateTimeHelper::toDateTime($row['installDate']);

            $this->_storedPluginInfo[$handle] = $row;
        }

        foreach ($this->_storedPluginInfo as $handle => $row) {
            // Skip disabled plugins
            if (!$row['enabled']) {
                continue;
            }

            try {
                $plugin = $this->createPlugin($handle, $row);
            } catch (InvalidPluginException) {
                $plugin = null;
            }

            if ($plugin !== null) {
                $hasVersionChanged = $this->hasPluginVersionNumberChanged($plugin);

                // If the plugin’s version just changed, make sure the old version is >= the min allowed version
                if (
                    $hasVersionChanged &&
                    isset($plugin->minVersionRequired) &&
                    $plugin->minVersionRequired &&
                    !str_starts_with($row['version'], 'dev-') &&
                    !str_ends_with($row['version'], '-dev') &&
                    version_compare($row['version'], $plugin->minVersionRequired, '<')
                ) {
                    throw new HttpException(200, Craft::t('app', 'You need to be on at least {plugin} {version} before you can update to {plugin} {targetVersion}.', [
                        'version' => $plugin->minVersionRequired,
                        'targetVersion' => $plugin->version,
                        'plugin' => $plugin->name,
                    ]));
                }

                // If we're not updating, check if the plugin’s version number changed, but not its schema version.
                if (!Craft::$app->getIsInMaintenanceMode() && $hasVersionChanged && !$this->isPluginUpdatePending($plugin)) {
                    // Update our record of the plugin’s version number
                    Db::update(Table::PLUGINS, [
                        'version' => $plugin->getVersion(),
                    ], [
                        'id' => $row['id'],
                    ]);
                }

                $this->_registerPlugin($plugin);
            }
        }
        unset($row);

        // Sort enabled plugins by their names
        ArrayHelper::multisort($this->_plugins, 'name', SORT_ASC, SORT_NATURAL | SORT_FLAG_CASE);

        $this->_loadingPlugins = false;
        $this->_pluginsLoaded = true;

        // Fire an 'afterLoadPlugins' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_LOAD_PLUGINS)) {
            $this->trigger(self::EVENT_AFTER_LOAD_PLUGINS);
        }
    }

    /**
     * Returns whether plugins have been loaded yet for this request.
     *
     * @return bool
     */
    public function arePluginsLoaded(): bool
    {
        return $this->_pluginsLoaded;
    }

    /**
     * Returns an enabled plugin by its handle.
     *
     * @param string $handle The plugin’s handle
     * @return PluginInterface|null The plugin, or null if it doesn’t exist
     */
    public function getPlugin(string $handle): ?PluginInterface
    {
        $this->loadPlugins();

        if (isset($this->_plugins[$handle])) {
            return $this->_plugins[$handle];
        }

        return null;
    }

    /**
     * Returns an enabled plugin by its package name.
     *
     * @param string $packageName The plugin’s package name
     * @return PluginInterface|null The plugin, or null if it doesn’t exist
     */
    public function getPluginByPackageName(string $packageName): ?PluginInterface
    {
        $this->loadPlugins();

        foreach ($this->_plugins as $plugin) {
            if ($plugin->packageName === $packageName) {
                return $plugin;
            }
        }

        return null;
    }

    /**
     * Returns the plugin handle that contains the given class, if any.
     *
     * The plugin may not actually be installed.
     *
     * @param string $class
     * @phpstan-param class-string $class
     * @return string|null The plugin handle, or null if it can’t be determined
     */
    public function getPluginHandleByClass(string $class): ?string
    {
        if (array_key_exists($class, $this->_classPluginHandles)) {
            return $this->_classPluginHandles[$class];
        }
        // Figure out the path to the folder that contains this class
        try {
            // Add a trailing slash so we don't get false positives
            $classPath = FileHelper::normalizePath(dirname((new ReflectionClass($class))->getFileName())) . DIRECTORY_SEPARATOR;
        } catch (ReflectionException) {
            return $this->_classPluginHandles[$class] = null;
        }

        // Find the plugin that contains this path (if any)
        foreach ($this->_composerPluginInfo as $handle => $info) {
            if (isset($info['basePath']) && str_starts_with($classPath, $info['basePath'] . DIRECTORY_SEPARATOR)) {
                return $this->_classPluginHandles[$class] = $handle;
            }
        }

        return $this->_classPluginHandles[$class] = null;
    }

    /**
     * Returns all the enabled plugins.
     *
     * @return PluginInterface[]
     */
    public function getAllPlugins(): array
    {
        $this->loadPlugins();

        return $this->_plugins;
    }

    /**
     * Enables a plugin by its handle.
     *
     * @param string $handle The plugin’s handle
     * @return bool Whether the plugin was enabled successfully
     * @throws InvalidPluginException if the plugin isn't installed
     */
    public function enablePlugin(string $handle): bool
    {
        if ($this->isPluginEnabled($handle)) {
            // It's already enabled
            return true;
        }

        if (($info = $this->getStoredPluginInfo($handle)) === null) {
            throw new InvalidPluginException($handle);
        }

        if (($plugin = $this->createPlugin($handle, $info)) === null) {
            throw new InvalidPluginException($handle);
        }

        // Fire a 'beforeEnablePlugin' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_ENABLE_PLUGIN)) {
            $this->trigger(self::EVENT_BEFORE_ENABLE_PLUGIN, new PluginEvent([
                'plugin' => $plugin,
            ]));
        }

        // Enable the plugin in the project config
        Craft::$app->getProjectConfig()->set(ProjectConfig::PATH_PLUGINS . '.' . $handle . '.enabled', true, "Enable plugin “{$handle}”");

        $this->_storedPluginInfo[$handle]['enabled'] = true;
        $this->_registerPlugin($plugin);

        // Fire an 'afterEnablePlugin' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_ENABLE_PLUGIN)) {
            $this->trigger(self::EVENT_AFTER_ENABLE_PLUGIN, new PluginEvent([
                'plugin' => $plugin,
            ]));
        }

        return true;
    }

    /**
     * Disables a plugin by its handle.
     *
     * @param string $handle The plugin’s handle
     * @return bool Whether the plugin was disabled successfully
     * @throws InvalidPluginException if the plugin isn’t installed
     */
    public function disablePlugin(string $handle): bool
    {
        if (!$this->isPluginInstalled($handle)) {
            throw new InvalidPluginException($handle);
        }

        if (!$this->isPluginEnabled($handle)) {
            // It's already disabled
            return true;
        }

        if (($plugin = $this->getPlugin($handle)) === null) {
            throw new InvalidPluginException($handle);
        }

        // Fire a 'beforeDisablePlugin' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DISABLE_PLUGIN)) {
            $this->trigger(self::EVENT_BEFORE_DISABLE_PLUGIN, new PluginEvent([
                'plugin' => $plugin,
            ]));
        }

        // Disable the plugin in the project config
        Craft::$app->getProjectConfig()->set(ProjectConfig::PATH_PLUGINS . '.' . $handle . '.enabled', false, "Disable plugin “{$handle}”");

        $this->_storedPluginInfo[$handle]['enabled'] = false;
        $this->_unregisterPlugin($plugin);

        // Fire an 'afterDisablePlugin' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DISABLE_PLUGIN)) {
            $this->trigger(self::EVENT_AFTER_DISABLE_PLUGIN, new PluginEvent([
                'plugin' => $plugin,
            ]));
        }

        return true;
    }

    /**
     * Installs a plugin by its handle.
     *
     * @param string $handle The plugin’s handle
     * @param string|null $edition The plugin’s edition
     * @return bool Whether the plugin was installed successfully.
     * @throws InvalidPluginException if the plugin doesn’t exist
     * @throws Throwable if reasons
     */
    public function installPlugin(string $handle, ?string $edition = null): bool
    {
        $this->loadPlugins();

        if ($this->getStoredPluginInfo($handle) !== null) {
            // It's already installed
            return true;
        }

        // Temporarily allow changes to the project config even if it's supposed to be read only
        $projectConfig = Craft::$app->getProjectConfig();
        $readOnly = $projectConfig->readOnly;
        $projectConfig->readOnly = false;

        $configKey = ProjectConfig::PATH_PLUGINS . '.' . $handle;

        $plugin = $this->createPlugin($handle);

        if ($plugin === null) {
            throw new InvalidPluginException($handle);
        }

        // Set the edition
        if ($edition === null) {
            // See if one is already set in the project config
            $edition = $projectConfig->get($configKey . '.edition');
        }
        $editions = $plugin::editions();
        if ($edition === null || !in_array($edition, $editions, true)) {
            $edition = reset($editions);
        }
        $plugin->edition = $edition;

        // Fire a 'beforeInstallPlugin' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_INSTALL_PLUGIN)) {
            $this->trigger(self::EVENT_BEFORE_INSTALL_PLUGIN, new PluginEvent([
                'plugin' => $plugin,
            ]));
        }

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();
        try {
            $info = [
                'handle' => $handle,
                'version' => $plugin->getVersion(),
                'schemaVersion' => $plugin->schemaVersion,
                'installDate' => Db::prepareDateForDb(new DateTime()),
            ];

            // Make sure the plugin doesn't have a row in the `plugins` or `migrations` tables first, just in case
            Db::delete(Table::PLUGINS, [
                'handle' => $handle,
            ]);
            Db::delete(Table::MIGRATIONS, [
                'track' => "plugin:$handle",
            ]);

            Db::insert(Table::PLUGINS, $info);

            $info['enabled'] = $projectConfig->get($configKey . '.enabled') ?? true;
            $info['installDate'] = DateTimeHelper::toDateTime($info['installDate']);
            $info['id'] = $db->getLastInsertID(Table::PLUGINS);

            $this->_setPluginMigrator($plugin);
            $plugin->install();
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();

            if ($db->getIsMysql()) {
                // Explicitly remove the plugins row just in case the transaction was implicitly committed
                Db::delete(Table::PLUGINS, [
                    'handle' => $handle,
                ]);
            }

            throw $e;
        }

        // Add the plugin to the project config
        $pluginData = [
            'edition' => $edition,
            'enabled' => true,
            'schemaVersion' => $plugin->schemaVersion,
        ];

        $projectConfig->set($configKey, $pluginData, "Install plugin “{$handle}”");

        $this->_storedPluginInfo[$handle] = $info;
        $this->_registerPlugin($plugin);

        // Fire an 'afterInstallPlugin' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_INSTALL_PLUGIN)) {
            $this->trigger(self::EVENT_AFTER_INSTALL_PLUGIN, new PluginEvent([
                'plugin' => $plugin,
            ]));
        }

        $projectConfig->readOnly = $readOnly;

        return true;
    }

    /**
     * Uninstalls a plugin by its handle.
     *
     * @param string $handle The plugin’s handle
     * @param bool $force Whether to force the plugin uninstallation, even if it is disabled, its
     * `uninstall()` method returns `false`, or its files aren’t present
     * @return bool Whether the plugin was uninstalled successfully
     * @throws InvalidPluginException if the plugin doesn’t exist
     * @throws Throwable if reasons
     */
    public function uninstallPlugin(string $handle, bool $force = false): bool
    {
        $this->loadPlugins();

        if (!$this->isPluginInstalled($handle)) {
            // It's already uninstalled
            return true;
        }

        $enabled = $this->isPluginEnabled($handle);

        if (!$enabled && !$force) {
            // Don't allow uninstalling disabled plugins, because that could be buggy
            // if the plugin was composer-updated while disabled, and its uninstall()
            // function is out of sync with what's actually in the database
            throw new InvalidPluginException($handle, 'Uninstalling disabled plugins is not allowed.');
        }

        // Temporarily allow changes to the project config even if it's supposed to be read only
        $projectConfig = Craft::$app->getProjectConfig();
        $readOnly = $projectConfig->readOnly;
        $projectConfig->readOnly = false;

        if (($plugin = $this->getPlugin($handle)) === null && !$force) {
            throw new InvalidPluginException($handle);
        }

        // Fire a 'beforeUninstallPlugin' event
        if ($plugin && $this->hasEventHandlers(self::EVENT_BEFORE_UNINSTALL_PLUGIN)) {
            $this->trigger(self::EVENT_BEFORE_UNINSTALL_PLUGIN, new PluginEvent([
                'plugin' => $plugin,
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Let the plugin uninstall itself first
            if ($plugin && $enabled) {
                try {
                    $plugin->uninstall();
                } catch (Throwable $e) {
                    if (!$force) {
                        throw $e;
                    }
                }
            }

            // Clean up the plugins and migrations tables
            $info = $this->getStoredPluginInfo($handle);
            if ($info !== null) {
                Db::delete(Table::PLUGINS, [
                    'id' => $info['id'],
                ]);
            }

            Db::delete(Table::MIGRATIONS, [
                'track' => "plugin:$handle",
            ]);

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Remove the plugin from the project config
        if ($projectConfig->get(ProjectConfig::PATH_PLUGINS . '.' . $handle, true)) {
            $projectConfig->remove(ProjectConfig::PATH_PLUGINS . '.' . $handle, "Uninstall the “{$handle}” plugin");
        }

        if ($plugin) {
            $this->_unregisterPlugin($plugin);
        }

        unset($this->_storedPluginInfo[$handle]);

        // Fire an 'afterUninstallPlugin' event
        if ($plugin && $this->hasEventHandlers(self::EVENT_AFTER_UNINSTALL_PLUGIN)) {
            $this->trigger(self::EVENT_AFTER_UNINSTALL_PLUGIN, new PluginEvent([
                'plugin' => $plugin,
            ]));
        }

        $projectConfig->readOnly = $readOnly;

        return true;
    }

    /**
     * Switches a plugin’s edition.
     *
     * @param string $handle The plugin’s handle
     * @param string $edition The plugin’s edition
     * @throws InvalidPluginException if the plugin doesn’t exist
     * @throws InvalidArgumentException if $edition is invalid
     * @throws Throwable if reasons
     */
    public function switchEdition(string $handle, string $edition): void
    {
        $info = $this->getPluginInfo($handle);

        /** @var string|PluginInterface $class */
        /** @phpstan-var class-string<PluginInterface>|PluginInterface $class */
        $class = $info['class'];

        if (!in_array($edition, $class::editions(), true)) {
            throw new InvalidArgumentException('Invalid plugin edition: ' . $edition);
        }

        // Update the project config
        Craft::$app->getProjectConfig()->set(ProjectConfig::PATH_PLUGINS . '.' . $handle . '.edition', $edition, "Switch edition for plugin “{$handle}”");

        if (isset($this->_storedPluginInfo[$handle])) {
            $this->_storedPluginInfo[$handle]['edition'] = $edition;
        }

        // If it's installed, update the instance and our locally stored info
        $plugin = $this->getPlugin($handle);
        if ($plugin !== null) {
            $plugin->edition = $edition;
        }
    }

    /**
     * Saves a plugin’s settings.
     *
     * @param PluginInterface $plugin The plugin
     * @param array $settings The plugin’s new settings
     * @return bool Whether the plugin’s settings were saved successfully
     */
    public function savePluginSettings(PluginInterface $plugin, array $settings): bool
    {
        // Save the settings on the plugin
        $plugin->getSettings()->setAttributes($settings, false);

        // Validate them, now that it's a model
        if ($plugin->getSettings()->validate() === false) {
            return false;
        }

        // Fire a 'beforeSavePluginSettings' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_PLUGIN_SETTINGS)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_PLUGIN_SETTINGS, new PluginEvent([
                'plugin' => $plugin,
            ]));
        }

        if (!$plugin->beforeSaveSettings()) {
            return false;
        }

        // Update the plugin’s settings in the project config
        $pluginSettings = $plugin->getSettings();
        $pluginSettings = $pluginSettings ? ProjectConfigHelper::packAssociativeArrays($pluginSettings->toArray()) : [];
        Craft::$app->getProjectConfig()->set(ProjectConfig::PATH_PLUGINS . '.' . $plugin->handle . '.settings', $pluginSettings, "Change settings for plugin “{$plugin->handle}”");

        $plugin->afterSaveSettings();

        // Fire an 'afterSavePluginSettings' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_PLUGIN_SETTINGS)) {
            $this->trigger(self::EVENT_AFTER_SAVE_PLUGIN_SETTINGS, new PluginEvent([
                'plugin' => $plugin,
            ]));
        }

        return true;
    }

    /**
     * Returns whether the given plugin’s version number has changed from what we have recorded in the database.
     *
     * @param PluginInterface $plugin The plugin
     * @return bool Whether the plugin’s version number has changed from what we have recorded in the database
     */
    public function hasPluginVersionNumberChanged(PluginInterface $plugin): bool
    {
        $this->loadPlugins();

        if (($info = $this->getStoredPluginInfo($plugin->id)) === null) {
            return false;
        }

        return $plugin->getVersion() !== $info['version'];
    }

    /**
     * Returns whether the given plugin’s local schema version is greater than the record we have in the database.
     *
     * @param PluginInterface $plugin The plugin
     * @return bool Whether the plugin’s local schema version is greater than the record we have in the database
     * @since 4.0.0
     */
    public function isPluginUpdatePending(PluginInterface $plugin): bool
    {
        $this->loadPlugins();

        if (($info = $this->getStoredPluginInfo($plugin->id)) === null) {
            return false;
        }

        return version_compare($plugin->schemaVersion, $info['schemaVersion'], '>');
    }

    /**
     * Returns whether a given plugin is installed (even if it's disabled).
     *
     * @param string $handle The plugin handle
     * @return bool
     */
    public function isPluginInstalled(string $handle): bool
    {
        $this->loadPlugins();
        return isset($this->_storedPluginInfo[$handle]);
    }

    /**
     * Returns whether a given plugin is installed and enabled.
     *
     * @param string $handle The plugin handle
     * @return bool
     */
    public function isPluginEnabled(string $handle): bool
    {
        $this->loadPlugins();
        return $this->_storedPluginInfo[$handle]['enabled'] ?? false;
    }

    /**
     * Returns whether a given plugin is installed but disabled.
     *
     * @param string $handle The plugin handle
     * @return bool
     */
    public function isPluginDisabled(string $handle): bool
    {
        return !$this->isPluginEnabled($handle) && $this->isPluginInstalled($handle);
    }

    /**
     * Returns the stored info for a given plugin.
     *
     * @param string $handle The plugin handle
     * @return array|null The stored info, if there is any
     */
    public function getStoredPluginInfo(string $handle): ?array
    {
        $this->loadPlugins();
        return $this->_storedPluginInfo[$handle] ?? null;
    }

    /**
     * Updates a plugin’s stored version & schema version to match what’s Composer-installed.
     *
     * @param PluginInterface $plugin
     * @return void
     * @throws InvalidPluginException if there’s no record of the plugin in the database
     * @since 3.7.13
     */
    public function updatePluginVersionInfo(PluginInterface $plugin): void
    {
        $success = (bool)Db::update(Table::PLUGINS, [
            'version' => $plugin->getVersion(),
            'schemaVersion' => $plugin->schemaVersion,
        ], [
            'handle' => $plugin->id,
        ]);

        if (!$success) {
            throw new InvalidPluginException($plugin->id);
        }

        // Update our cache of the versions
        $this->loadPlugins();
        if (isset($this->_storedPluginInfo[$plugin->id])) {
            $this->_storedPluginInfo[$plugin->id]['version'] = $plugin->getVersion();
            $this->_storedPluginInfo[$plugin->id]['schemaVersion'] = $plugin->schemaVersion;
        }

        // Only update the schema version if it's changed from what's in the file,
        // so we don't accidentally overwrite other pending changes
        $projectConfig = Craft::$app->getProjectConfig();
        $key = ProjectConfig::PATH_PLUGINS . ".$plugin->id.schemaVersion";

        if ($projectConfig->get($key, true) !== $plugin->schemaVersion) {
            Craft::$app->getProjectConfig()->set($key, $plugin->schemaVersion, "Update plugin schema version for “{$plugin->handle}”");
        }
    }

    /**
     * Returns the Composer-supplied info
     *
     * @param string|null $handle The plugin handle. If null is passed, info for all Composer-installed plugins will be returned.
     * @return array|null The plugin info, or null if an unknown handle was passed.
     */
    public function getComposerPluginInfo(?string $handle = null): ?array
    {
        if ($handle === null) {
            return $this->_composerPluginInfo;
        }
        return $this->_composerPluginInfo[$handle] ?? null;
    }

    /**
     * Creates and returns a new plugin instance based on its handle.
     *
     * @param string $handle The plugin’s handle
     * @param array|null $info The plugin’s stored info, if any
     * @return PluginInterface|null
     * @throws InvalidPluginException if $handle is invalid
     */
    public function createPlugin(string $handle, ?array $info = null): ?PluginInterface
    {
        if (!isset($this->_composerPluginInfo[$handle])) {
            throw new InvalidPluginException($handle);
        }

        $config = $this->_composerPluginInfo[$handle];

        if (isset($config['aliases'])) {
            foreach ($config['aliases'] as $alias => $path) {
                Craft::setAlias($alias, $path);
            }

            // Unset them so we don't end up calling Module::setAliases()
            unset($config['aliases']);
        }

        /** @var string|PluginInterface $class */
        /** @phpstan-var class-string<PluginInterface>|PluginInterface $class */
        $class = $config['class'];

        // Make sure the class exists and it implements PluginInterface
        if (!is_subclass_of($class, PluginInterface::class)) {
            return null;
        }

        // Merge in the plugin’s dynamic config
        $config = ArrayHelper::merge($config, $class::config());

        // Is it installed?
        if ($info !== null) {
            $config['isInstalled'] = true;

            // Set the edition
            $config['edition'] = $info['edition'] ?? 'standard';
            $editions = $class::editions();
            if (!in_array($config['edition'], $editions, true)) {
                $config['edition'] = reset($editions);
            }

            $settings = array_merge(
                $info['settings'] ?? [],
                Craft::$app->getConfig()->getConfigFromFile($handle)
            );

            if ($settings !== []) {
                $config['settings'] = $settings;
            }

            // Merge in the custom config, if there is one
            if (isset($this->pluginConfigs[$handle])) {
                $config = ArrayHelper::merge($config, $this->pluginConfigs[$handle]);
            }
        }

        // Create the plugin
        /** @var Plugin $plugin */
        $plugin = Craft::createObject($config, [$handle, Craft::$app]);
        $this->_setPluginMigrator($plugin);
        return $plugin;
    }

    /**
     * Returns info about all of the plugins we can find, whether they’re installed or not.
     *
     * @return array
     */
    public function getAllPluginInfo(): array
    {
        $this->loadPlugins();

        // Get the info arrays
        $info = [];

        foreach (array_keys($this->_composerPluginInfo) as $handle) {
            $info[$handle] = $this->getPluginInfo($handle);
        }

        // Sort plugins by their names
        ArrayHelper::multisort($info, 'name', SORT_ASC, SORT_NATURAL | SORT_FLAG_CASE);

        return $info;
    }

    /**
     * Returns info about a plugin, whether it's installed or not.
     *
     * @param string $handle The plugin’s handle
     * @return array
     * @throws InvalidPluginException if the plugin isn't Composer-installed
     */
    public function getPluginInfo(string $handle): array
    {
        if (!isset($this->_composerPluginInfo[$handle])) {
            throw new InvalidPluginException($handle);
        }

        $pluginInfo = $this->getStoredPluginInfo($handle);

        // Get the plugin if it's enabled
        $plugin = $this->getPlugin($handle);

        $info = array_merge([
            'developer' => null,
            'developerUrl' => null,
            'description' => null,
            'documentationUrl' => null,
        ], $this->_composerPluginInfo[$handle]);

        $edition = $pluginInfo['edition'] ?? 'standard';
        if ($plugin) {
            $editions = $plugin::editions();
            if (!in_array($edition, $editions, true)) {
                $edition = reset($editions);
            }
        } else {
            $editions = ['standard'];
        }

        $info['isInstalled'] = $installed = $pluginInfo !== null;
        $info['isEnabled'] = $plugin !== null;
        $info['private'] = str_starts_with($handle, '_');
        $info['moduleId'] = $handle;
        $info['edition'] = $edition;
        $info['hasMultipleEditions'] = count($editions) > 1;
        $info['hasCpSettings'] = ($plugin !== null && $plugin->hasCpSettings);
        $info['licenseKey'] = $pluginInfo['licenseKey'] ?? null;

        $licenseInfo = Craft::$app->getCache()->get('licenseInfo') ?? [];
        $pluginCacheKey = StringHelper::ensureLeft($handle, 'plugin-');
        $info['licenseId'] = $licenseInfo[$pluginCacheKey]['id'] ?? null;
        $info['licensedEdition'] = $licenseInfo[$pluginCacheKey]['edition'] ?? null;
        $info['licenseKeyStatus'] = $licenseInfo[$pluginCacheKey]['status'] ?? LicenseKeyStatus::Unknown;
        $info['licenseIssues'] = $installed ? $this->getLicenseIssues($handle) : [];

        $info['isTrial'] = (
            $installed &&
            (
                $info['licenseKeyStatus'] === LicenseKeyStatus::Trial ||
                (
                    $info['licenseKeyStatus'] === LicenseKeyStatus::Valid &&
                    !empty($pluginInfo['licensedEdition'])
                    && $pluginInfo['licensedEdition'] !== $edition
                )
            )
        );

        // An upgrade is available if the plugin is in trial or licensed to less than the best edition
        $info['upgradeAvailable'] = (
            $info['isTrial'] ||
            (
                $info['hasMultipleEditions'] &&
                (
                    (!empty($pluginInfo['licensedEdition']) && $pluginInfo['licensedEdition'] !== end($editions)) ||
                    ($pluginInfo['edition'] ?? 'standard') !== end($editions)
                )
            )
        );

        return $info;
    }

    /**
     * Returns whether a plugin has licensing issues.
     *
     * @param string $handle
     * @return bool
     */
    public function hasIssues(string $handle): bool
    {
        return !empty($this->getLicenseIssues($handle));
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
     * @return string[]
     */
    public function getLicenseIssues(string $handle): array
    {
        $pluginInfo = $this->getStoredPluginInfo($handle);

        if ($pluginInfo === null) {
            return [];
        }

        $status = $pluginInfo['licenseKeyStatus'] ?? LicenseKeyStatus::Unknown;

        if ($status === LicenseKeyStatus::Unknown) {
            // Either we don't know yet, or the plugin is free
            return [];
        }

        $issues = [];

        // Make sure they're allowed to run the current edition
        $canTestEditions = Craft::$app->getCanTestEditions();
        if (
            !$canTestEditions &&
            isset($pluginInfo['edition'], $pluginInfo['licensedEdition']) &&
            $pluginInfo['edition'] !== $pluginInfo['licensedEdition']
        ) {
            $issues[] = 'wrong_edition';
        }

        // General license issues
        switch ($pluginInfo['licenseKeyStatus']) {
            case LicenseKeyStatus::Trial:
                if (!$canTestEditions) {
                    $issues[] = empty($pluginInfo['licenseKey']) ? 'required' : 'no_trials';
                }
                break;
            case LicenseKeyStatus::Invalid:
            case LicenseKeyStatus::Mismatched:
            case LicenseKeyStatus::Astray:
                $issues[] = $pluginInfo['licenseKeyStatus'];
                break;
        }

        return $issues;
    }

    /**
     * Returns a plugin’s SVG icon.
     *
     * @param string $handle The plugin’s handle
     * @return string The given plugin’s SVG icon
     */
    public function getPluginIconSvg(string $handle): string
    {
        // If it's installed, let the plugin say where it lives
        if (($plugin = $this->getPlugin($handle)) !== null) {
            $basePath = $plugin->getBasePath();
        } else {
            if (($basePath = $this->_composerPluginInfo[$handle]['basePath'] ?? false) !== false) {
                $basePath = Craft::getAlias($basePath);
            }
        }

        $iconPath = ($basePath !== false) ? $basePath . DIRECTORY_SEPARATOR . 'icon.svg' : false;

        if ($iconPath === false || !is_file($iconPath) || !FileHelper::isSvg($iconPath)) {
            $iconPath = Craft::getAlias('@appicons/default-plugin.svg');
        }

        return file_get_contents($iconPath);
    }

    /**
     * Returns the license key stored for a given plugin, if it was purchased through the Store.
     *
     * @param string $handle The plugin’s handle
     * @return string|null The plugin’s license key, or null if it isn’t known
     * @throws InvalidLicenseKeyException
     */
    public function getPluginLicenseKey(string $handle): ?string
    {
        $licenseKey = $this->getStoredPluginInfo($handle)['licenseKey'] ?? null;
        return $this->normalizePluginLicenseKey(App::parseEnv($licenseKey));
    }

    /**
     * Sets a plugin’s license key.
     *
     * Note this should *not* be used to store license keys generated by third party stores.
     *
     * @param string $handle The plugin’s handle
     * @param string|null $licenseKey The plugin’s license key
     * @return bool Whether the license key was updated successfully
     * @throws InvalidPluginException if the plugin isn't installed
     * @throws InvalidLicenseKeyException if $licenseKey is invalid
     */
    public function setPluginLicenseKey(string $handle, ?string $licenseKey = null): bool
    {
        // Validate the license key
        $normalizedLicenseKey = $this->normalizePluginLicenseKey($licenseKey);

        // If the license key is set to an empty environment variable, set the environment variable's value
        $oldLicenseKey = $this->getStoredPluginInfo($handle)['licenseKey'] ?? null;
        // https://github.com/craftcms/cms/issues/12687 - check if the .env file exists first
        if (
            preg_match('/^\$(\w+)$/', $oldLicenseKey, $matches) &&
            App::env($matches[1]) === '' &&
            file_exists(Craft::$app->getConfig()->getDotEnvPath())
        ) {
            Craft::$app->getConfig()->setDotEnvVar($matches[1], $normalizedLicenseKey);
        } else {
            // Set the plugin's license key in the project config
            Craft::$app->getProjectConfig()->set(sprintf('%s.%s.licenseKey', ProjectConfig::PATH_PLUGINS, $handle), $normalizedLicenseKey, "Set license key for plugin “{$handle}”");

            // Update our cache of it
            $this->loadPlugins();
            if (isset($this->_storedPluginInfo[$handle])) {
                $this->_storedPluginInfo[$handle]['licenseKey'] = $normalizedLicenseKey;
            }
        }

        // Clear the plugin's cached license key status
        $cache = Craft::$app->getCache();
        $licenseInfo = $cache->get('licenseInfo') ?? [];
        if (isset($licenseInfo[$handle])) {
            unset($licenseInfo[$handle]);
            $cache->set('licenseInfo', $licenseInfo);
        }

        return true;
    }

    /**
     * Normalizes a plugin license key.
     *
     * @param string|null $licenseKey
     * @return string|null
     * @throws InvalidLicenseKeyException
     */
    public function normalizePluginLicenseKey(?string $licenseKey = null): ?string
    {
        if (empty($licenseKey)) {
            return null;
        }

        if (str_starts_with($licenseKey, '$')) {
            return $licenseKey;
        }

        // Normalize to just uppercase numbers/letters
        $licenseKey = mb_strtoupper($licenseKey);
        $licenseKey = preg_replace('/[^A-Z0-9]/', '', $licenseKey);

        if (strlen($licenseKey) != 24) {
            // Invalid key
            throw new InvalidLicenseKeyException($licenseKey);
        }

        return $licenseKey;
    }

    /**
     * Returns the license key status of a given plugin.
     *
     * @param string $handle The plugin’s handle
     * @return string
     */
    public function getPluginLicenseKeyStatus(string $handle): string
    {
        return $this->getStoredPluginInfo($handle)['licenseKeyStatus'] ?? LicenseKeyStatus::Unknown;
    }

    /**
     * Sets the license key status for a given plugin.
     *
     * @param string $handle The plugin’s handle
     * @param string|null $licenseKeyStatus The plugin’s license key status
     * @param string|null $licensedEdition The plugin’s licensed edition, if the key is valid
     * @deprecated in 4.4.0
     */
    public function setPluginLicenseKeyStatus(string $handle, ?string $licenseKeyStatus = null, ?string $licensedEdition = null): void
    {
        // this is not the way
    }

    /**
     * Returns a Query object prepped for retrieving sections.
     *
     * @return Query
     */
    private function _createPluginQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'handle',
                'version',
                'schemaVersion',
                'installDate',
            ])
            ->from([Table::PLUGINS]);
    }

    /**
     * Converts old school camelCase handles to kebab-case.
     *
     * @param string $handle
     * @return string
     */
    private function _normalizeHandle(string $handle): string
    {
        if (strtolower($handle) !== $handle) {
            $handle = preg_replace('/\-{2,}/', '-', Inflector::camel2id($handle));
        }

        return $handle;
    }

    /**
     * Registers a plugin internally and as an application module.
     *
     * This should only be called for enabled plugins
     *
     * @param PluginInterface $plugin The plugin
     */
    private function _registerPlugin(PluginInterface $plugin): void
    {
        $this->_plugins[$plugin->id] = $plugin;
        Craft::$app->setModule($plugin->id, $plugin);
    }

    /**
     * Unregisters a plugin internally and as an application module.
     *
     * @param PluginInterface $plugin The plugin
     */
    private function _unregisterPlugin(PluginInterface $plugin): void
    {
        unset($this->_plugins[$plugin->id]);
        Craft::$app->setModule($plugin->id, null);
    }

    /**
     * Sets the 'migrator' component on a plugin.
     *
     * @param PluginInterface $plugin The plugin
     */
    private function _setPluginMigrator(PluginInterface $plugin): void
    {
        $ref = new ReflectionClass($plugin);
        $ns = $ref->getNamespaceName();
        $plugin->set('migrator', [
            'class' => MigrationManager::class,
            'track' => "plugin:$plugin->id",
            'migrationNamespace' => ($ns ? $ns . '\\' : '') . 'migrations',
            'migrationPath' => $plugin->getBasePath() . DIRECTORY_SEPARATOR . 'migrations',
        ]);
    }

    /**
     * Load config data for plugin by its handle.
     *
     * @param string $handle
     * @return array
     * @throws InvalidPluginException if plugin not found
     */
    private function _getPluginConfigData(string $handle): array
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $configKey = ProjectConfig::PATH_PLUGINS . '.' . $handle;
        $data = $projectConfig->get($configKey);

        if (!empty($data['settings'])) {
            $data['settings'] = ProjectConfigHelper::unpackAssociativeArrays($data['settings']);
        }

        if (!$data) {
            throw new InvalidPluginException($handle);
        }

        // Force disable it?
        if (
            $this->_forceDisabledPlugins === '*' ||
            (is_array($this->_forceDisabledPlugins) && isset($this->_forceDisabledPlugins[$handle]))
        ) {
            $data['enabled'] = false;
        }

        return $data;
    }
}
