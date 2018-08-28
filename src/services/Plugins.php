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
use craft\enums\LicenseKeyStatus;
use craft\errors\InvalidLicenseKeyException;
use craft\errors\InvalidPluginException;
use craft\events\PluginEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use yii\base\Component;
use yii\db\Exception;
use yii\helpers\Inflector;
use yii\web\HttpException;

/**
 * The Plugins service provides APIs for managing plugins.
 * An instance of the Plugins service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getPlugins()|`Craft::$app->plugins`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Plugins extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event \yii\base\Event The event that is triggered before any plugins have been loaded
     */
    const EVENT_BEFORE_LOAD_PLUGINS = 'beforeLoadPlugins';

    /**
     * @event \yii\base\Event The event that is triggered after all plugins have been loaded
     */
    const EVENT_AFTER_LOAD_PLUGINS = 'afterLoadPlugins';

    /**
     * @event PluginEvent The event that is triggered before a plugin is enabled
     */
    const EVENT_BEFORE_ENABLE_PLUGIN = 'beforeEnablePlugin';
    /**
     * @event PluginEvent The event that is triggered before a plugin is enabled
     */
    const EVENT_AFTER_ENABLE_PLUGIN = 'afterEnablePlugin';

    /**
     * @event PluginEvent The event that is triggered before a plugin is disabled
     */
    const EVENT_BEFORE_DISABLE_PLUGIN = 'beforeDisablePlugin';
    /**
     * @event PluginEvent The event that is triggered before a plugin is disabled
     */
    const EVENT_AFTER_DISABLE_PLUGIN = 'afterDisablePlugin';

    /**
     * @event PluginEvent The event that is triggered before a plugin is installed
     */
    const EVENT_BEFORE_INSTALL_PLUGIN = 'beforeInstallPlugin';

    /**
     * @event PluginEvent The event that is triggered before a plugin is installed
     */
    const EVENT_AFTER_INSTALL_PLUGIN = 'afterInstallPlugin';

    /**
     * @event PluginEvent The event that is triggered before a plugin is uninstalled
     */
    const EVENT_BEFORE_UNINSTALL_PLUGIN = 'beforeUninstallPlugin';

    /**
     * @event PluginEvent The event that is triggered before a plugin is uninstalled
     */
    const EVENT_AFTER_UNINSTALL_PLUGIN = 'afterUninstallPlugin';

    /**
     * @event PluginEvent The event that is triggered before a plugin's settings are saved
     */
    const EVENT_BEFORE_SAVE_PLUGIN_SETTINGS = 'beforeSavePluginSettings';

    /**
     * @event PluginEvent The event that is triggered before a plugin's settings are saved
     */
    const EVENT_AFTER_SAVE_PLUGIN_SETTINGS = 'afterSavePluginSettings';

    // Properties
    // =========================================================================

    /**
     * @var bool Whether plugins have been loaded yet for this request
     */
    private $_pluginsLoaded = false;

    /**
     * @var bool Whether plugins are in the middle of being loaded
     */
    private $_loadingPlugins = false;

    /**
     * @var PluginInterface[] All the enabled plugins, indexed by handles
     */
    private $_plugins = [];

    /**
     * @var array|null Plugin info provided by Composer, indexed by handles
     */
    private $_composerPluginInfo;

    /**
     * @var array|null All of the stored info for enabled plugins, indexed by handles
     */
    private $_enabledPluginInfo;

    /**
     * @var array|null All of the stored info for disabled plugins, indexed by handles
     */
    private $_disabledPluginInfo;

    /**
     * @var string[] Cache for [[getPluginHandleByClass()]]
     */
    private $_classPluginHandles = [];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->_composerPluginInfo = [];

        $path = Craft::$app->getVendorPath() . DIRECTORY_SEPARATOR . 'craftcms' . DIRECTORY_SEPARATOR . 'plugins.php';

        if (file_exists($path)) {
            /** @var array $plugins */
            $plugins = require $path;

            foreach ($plugins as $packageName => $plugin) {
                $plugin['packageName'] = $packageName;
                // Normalize the base path (and find the actual path, not a possibly-symlinked path)
                if (isset($plugin['basePath'])) {
                    $plugin['basePath'] = FileHelper::normalizePath(realpath($plugin['basePath']));
                }
                $handle = $this->_normalizeHandle(ArrayHelper::remove($plugin, 'handle'));
                $this->_composerPluginInfo[$handle] = $plugin;
            }
        }
    }

    /**
     * Loads the enabled plugins.
     */
    public function loadPlugins()
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
        // todo: remove try/catch after next breakpoint
        try {
            $this->_enabledPluginInfo = $this->_createPluginQuery()
                ->where(['enabled' => true])
                ->indexBy('handle')
                ->all();
        } catch (Exception $e) {
            $this->_enabledPluginInfo = [];
        }

        foreach ($this->_enabledPluginInfo as $handle => &$row) {
            // Clean up the row data
            $row['settings'] = Json::decode($row['settings']);
            $row['installDate'] = DateTimeHelper::toDateTime($row['installDate']);

            try {
                $plugin = $this->createPlugin($handle, $row);
            } catch (InvalidPluginException $e) {
                $plugin = null;
            }

            if ($plugin !== null) {
                // If we're not updating, check if the plugin's version number changed, but not its schema version.
                if (!Craft::$app->getIsInMaintenanceMode() && $this->hasPluginVersionNumberChanged($plugin) && !$this->doesPluginRequireDatabaseUpdate($plugin)) {

                    /** @var Plugin $plugin */
                    if (
                        $plugin->minVersionRequired &&
                        strpos($row['version'], 'dev-') !== 0 &&
                        version_compare($row['version'], $plugin->minVersionRequired, '<')
                    ) {
                        throw new HttpException(200, Craft::t('app', 'You need to be on at least {plugin} {version} before you can update to {plugin} {targetVersion}.', [
                            'version' => $plugin->minVersionRequired,
                            'targetVersion' => $plugin->version,
                            'plugin' => $plugin->name
                        ]));
                    }

                    // Update our record of the plugin's version number
                    Craft::$app->getDb()->createCommand()
                        ->update(
                            '{{%plugins}}',
                            ['version' => $plugin->getVersion()],
                            ['id' => $row['id']])
                        ->execute();
                }

                $this->_registerPlugin($plugin);
            }
        }
        unset($row);

        // Sort plugins by their names
        $names = array_column($this->_plugins, 'name');
        array_multisort($names, SORT_NATURAL | SORT_FLAG_CASE, $this->_plugins);

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
    public function getPlugin(string $handle)
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
    public function getPluginByPackageName(string $packageName)
    {
        $this->loadPlugins();

        foreach ($this->_plugins as $plugin) {
            /** @var Plugin $plugin */
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
     * @return string|null The plugin handle, or null if it can’t be determined
     */
    public function getPluginHandleByClass(string $class)
    {
        if (array_key_exists($class, $this->_classPluginHandles)) {
            return $this->_classPluginHandles[$class];
        }
        // Figure out the path to the folder that contains this class
        try {
            // Add a trailing slash so we don't get false positives
            $classPath = FileHelper::normalizePath(dirname((new \ReflectionClass($class))->getFileName())) . DIRECTORY_SEPARATOR;
        } catch (\ReflectionException $e) {
            return $this->_classPluginHandles[$class] = null;
        }

        // Find the plugin that contains this path (if any)
        foreach ($this->_composerPluginInfo as $handle => $info) {
            if (isset($info['basePath']) && strpos($classPath, $info['basePath'] . DIRECTORY_SEPARATOR) === 0) {
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
                'plugin' => $plugin
            ]));
        }

        Craft::$app->getDb()->createCommand()
            ->update('{{%plugins}}', ['enabled' => true], ['id' => $info['id']])
            ->execute();

        $this->_enabledPluginInfo[$handle] = $info;
        $this->_registerPlugin($plugin);

        // Fire an 'afterEnablePlugin' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_ENABLE_PLUGIN)) {
            $this->trigger(self::EVENT_AFTER_ENABLE_PLUGIN, new PluginEvent([
                'plugin' => $plugin
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
                'plugin' => $plugin
            ]));
        }

        Craft::$app->getDb()->createCommand()
            ->update('{{%plugins}}', ['enabled' => false], ['handle' => $handle])
            ->execute();

        unset($this->_enabledPluginInfo[$handle]);
        $this->_unregisterPlugin($plugin);

        // Fire an 'afterDisablePlugin' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DISABLE_PLUGIN)) {
            $this->trigger(self::EVENT_AFTER_DISABLE_PLUGIN, new PluginEvent([
                'plugin' => $plugin
            ]));
        }

        return true;
    }

    /**
     * Installs a plugin by its handle.
     *
     * @param string $handle The plugin’s handle
     * @return bool Whether the plugin was installed successfully.
     * @throws InvalidPluginException if the plugin doesn’t exist
     * @throws \Throwable if reasons
     */
    public function installPlugin(string $handle): bool
    {
        $this->loadPlugins();

        if ($this->getStoredPluginInfo($handle) !== null) {
            // It's already installed
            return true;
        }

        /** @var Plugin $plugin */
        $plugin = $this->createPlugin($handle);

        // Fire a 'beforeInstallPlugin' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_INSTALL_PLUGIN)) {
            $this->trigger(self::EVENT_BEFORE_INSTALL_PLUGIN, new PluginEvent([
                'plugin' => $plugin
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $info = [
                'handle' => $handle,
                'version' => $plugin->getVersion(),
                'schemaVersion' => $plugin->schemaVersion,
                'enabled' => true,
                'installDate' => Db::prepareDateForDb(new \DateTime()),
            ];

            Craft::$app->getDb()->createCommand()
                ->insert('{{%plugins}}', $info)
                ->execute();

            $info['installDate'] = DateTimeHelper::toDateTime($info['installDate']);
            $info['id'] = Craft::$app->getDb()->getLastInsertID('{{%plugins}}');

            $this->_setPluginMigrator($plugin, $info['id']);

            if ($plugin->install() === false) {
                $transaction->rollBack();

                return false;
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        $this->_enabledPluginInfo[$handle] = $info;
        $this->_registerPlugin($plugin);

        // Fire an 'afterInstallPlugin' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_INSTALL_PLUGIN)) {
            $this->trigger(self::EVENT_AFTER_INSTALL_PLUGIN, new PluginEvent([
                'plugin' => $plugin
            ]));
        }

        return true;
    }

    /**
     * Uninstalls a plugin by its handle.
     *
     * @param string $handle The plugin’s handle
     * @return bool Whether the plugin was uninstalled successfully
     * @throws InvalidPluginException if the plugin doesn’t exist
     * @throws \Throwable if reasons
     */
    public function uninstallPlugin(string $handle): bool
    {
        $this->loadPlugins();

        if (!$this->isPluginEnabled($handle)) {
            // Don't allow uninstalling disabled plugins, because that could be buggy
            // if the plugin was composer-updated while disabled, and its uninstall()
            // function is out of sync with what's actually in the database
            if ($this->isPluginInstalled($handle)) {
                throw new InvalidPluginException($handle, 'Uninstalling disabled plugins is not allowed.');
            }
            // It's already uninstalled
            return true;
        }

        if (($plugin = $this->getPlugin($handle)) === null) {
            throw new InvalidPluginException($handle);
        }

        // Fire a 'beforeUninstallPlugin' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_UNINSTALL_PLUGIN)) {
            $this->trigger(self::EVENT_BEFORE_UNINSTALL_PLUGIN, new PluginEvent([
                'plugin' => $plugin
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Let the plugin uninstall itself first
            if ($plugin->uninstall() === false) {
                $transaction->rollBack();

                return false;
            }

            // Clean up the plugins and migrations tables
            $id = $this->getStoredPluginInfo($handle)['id'];

            Craft::$app->getDb()->createCommand()
                ->delete('{{%plugins}}', ['id' => $id])
                ->execute();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        $this->_unregisterPlugin($plugin);
        unset($this->_enabledPluginInfo[$handle]);

        // Fire an 'afterUninstallPlugin' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_UNINSTALL_PLUGIN)) {
            $this->trigger(self::EVENT_AFTER_UNINSTALL_PLUGIN, new PluginEvent([
                'plugin' => $plugin
            ]));
        }

        return true;
    }

    /**
     * Saves a plugin's settings.
     *
     * @param PluginInterface $plugin The plugin
     * @param array $settings The plugin’s new settings
     * @return bool Whether the plugin’s settings were saved successfully
     */
    public function savePluginSettings(PluginInterface $plugin, array $settings): bool
    {
        /** @var Plugin $plugin */
        // Save the settings on the plugin
        $plugin->getSettings()->setAttributes($settings, false);

        // Validate them, now that it's a model
        if ($plugin->getSettings()->validate() === false) {
            return false;
        }

        // Fire a 'beforeSavePluginSettings' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_PLUGIN_SETTINGS)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_PLUGIN_SETTINGS, new PluginEvent([
                'plugin' => $plugin
            ]));
        }

        if (!$plugin->beforeSaveSettings()) {
            return false;
        }

        $affectedRows = Craft::$app->getDb()->createCommand()
            ->update(
                '{{%plugins}}',
                ['settings' => Json::encode($plugin->getSettings())],
                ['handle' => $plugin->id])
            ->execute();

        $plugin->afterSaveSettings();

        // Fire an 'afterSavePluginSettings' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_PLUGIN_SETTINGS)) {
            $this->trigger(self::EVENT_AFTER_SAVE_PLUGIN_SETTINGS, new PluginEvent([
                'plugin' => $plugin
            ]));
        }

        return (bool)$affectedRows;
    }

    /**
     * Returns whether the given plugin’s version number has changed from what we have recorded in the database.
     *
     * @param PluginInterface $plugin The plugin
     * @return bool Whether the plugin’s version number has changed from what we have recorded in the database
     */
    public function hasPluginVersionNumberChanged(PluginInterface $plugin): bool
    {
        /** @var Plugin $plugin */
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
     */
    public function doesPluginRequireDatabaseUpdate(PluginInterface $plugin): bool
    {
        /** @var Plugin $plugin */
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

        if (isset($this->_enabledPluginInfo[$handle])) {
            return true;
        }

        return $this->_createPluginQuery()
            ->where(['handle' => $handle])
            ->exists();
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
        return isset($this->_enabledPluginInfo[$handle]);
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
    public function getStoredPluginInfo(string $handle)
    {
        $this->loadPlugins();

        if (isset($this->_enabledPluginInfo[$handle])) {
            return $this->_enabledPluginInfo[$handle];
        }

        $row = $this->_createPluginQuery()
            ->where(['handle' => $handle])
            ->one();

        if (!$row) {
            return null;
        }

        $row['settings'] = Json::decode($row['settings']);
        $row['installDate'] = DateTimeHelper::toDateTime($row['installDate']);

        return $row;
    }

    /**
     * Returns the Composer-supplied info
     *
     * @param string|null $handle The plugin handle. If null is passed, info for all Composer-installed plugins will be returned.
     * @return array|null The plugin info, or null if an unknown handle was passed.
     */
    public function getComposerPluginInfo(string $handle = null)
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
     * @param array|null $row The plugin’s row in the plugins table, if any
     * @return PluginInterface
     * @throws InvalidPluginException if $handle is invalid
     */
    public function createPlugin(string $handle, array $row = null)
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

        $class = $config['class'];

        // Make sure the class exists and it implements PluginInterface
        if (!is_subclass_of($class, PluginInterface::class)) {
            return null;
        }

        // Is it installed?
        if ($row !== null) {
            $config['isInstalled'] = true;

            $settings = array_merge(
                $row['settings'] ?? [],
                Craft::$app->getConfig()->getConfigFromFile($handle)
            );

            if ($settings !== []) {
                $config['settings'] = $settings;
            }
        }

        // Create the plugin
        /** @var Plugin $plugin */
        $plugin = Craft::createObject($config, [$handle, Craft::$app]);

        if ($row !== null) {
            $this->_setPluginMigrator($plugin, $row['id']);
        }

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
        $names = [];

        foreach (array_keys($this->_composerPluginInfo) as $handle) {
            $info[$handle] = $this->getPluginInfo($handle);
            $names[] = $info[$handle]['name'];
        }

        // Sort plugins by their names
        array_multisort($names, SORT_NATURAL | SORT_FLAG_CASE, $info);

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

        // Get the info in the DB, if it's installed
        $this->_loadDisabledPluginInfo();
        $pluginInfo = $this->_enabledPluginInfo[$handle] ?? $this->_disabledPluginInfo[$handle] ?? null;

        // Get the plugin if it's enabled
        /** @var Plugin|null $plugin */
        $plugin = $this->getPlugin($handle);

        $info = array_merge([
            'developer' => null,
            'developerUrl' => null,
            'description' => null,
            'documentationUrl' => null,
        ], $this->_composerPluginInfo[$handle]);

        $info['isInstalled'] = $installed = $pluginInfo !== null;
        $info['isEnabled'] = $plugin !== null;
        $info['moduleId'] = $handle;
        $info['hasCpSettings'] = ($plugin !== null && $plugin->hasCpSettings);
        $info['licenseKey'] = $key = $pluginInfo['licenseKey'] ?? null;
        $info['licenseKeyStatus'] = $status = $pluginInfo['licenseKeyStatus'] ?? LicenseKeyStatus::Unknown;
        $info['hasIssues'] = $hasIssues = $installed ? $this->hasIssues($handle) : false;

        if ($hasIssues) {
            switch ($status) {
                case LicenseKeyStatus::Mismatched:
                    $info['licenseStatusMessage'] = Craft::t('app', 'This license is tied to another Craft install. Visit {url} to resolve.', [
                        'url' => '<a href="https://id.craftcms.com" rel="noopener" target="_blank">id.craftcms.com</a>',
                    ]);
                    break;
                case LicenseKeyStatus::Astray:
                    $info['licenseStatusMessage'] = Craft::t('app', 'This license isn’t allowed to run version {version}.', [
                        'version' => $this->_composerPluginInfo[$handle]['version'],
                    ]);
                    break;
                default:
                    $info['licenseStatusMessage'] = $key ? Craft::t('app', 'Your license key is invalid.') : Craft::t('app', 'A license key is required.');
                    break;
            }
        } else {
            $info['licenseStatusMessage'] = null;
        }

        return $info;
    }

    /**
     * Returns whether a plugin has licensing issues.
     *
     * @param string $handle
     * @return bool
     * @throws InvalidPluginException if the plugin isn't installed
     */
    public function hasIssues(string $handle): bool
    {
        if (isset($this->_enabledPluginInfo[$handle])) {
            $pluginInfo = $this->_enabledPluginInfo[$handle];
        } else {
            $this->_loadDisabledPluginInfo();
            if (!isset($this->_disabledPluginInfo[$handle])) {
                return false;
            }
            $pluginInfo = $this->_disabledPluginInfo[$handle];
        }

        $status = $pluginInfo['licenseKeyStatus'] ?? LicenseKeyStatus::Unknown;

        return (
            $status !== LicenseKeyStatus::Valid &&
            $status !== LicenseKeyStatus::Unknown &&
            (
                $status !== LicenseKeyStatus::Invalid ||
                !empty($pluginInfo['licenseKey']) ||
                !Craft::$app->getCanTestEditions()
            )
        );
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
            /** @var Plugin $plugin */
            $basePath = $plugin->getBasePath();
        } else {
            if (($basePath = $this->_composerPluginInfo[$handle]['basePath'] ?? false) !== false) {
                $basePath = Craft::getAlias($basePath);
            }
        }

        $iconPath = ($basePath !== false) ? $basePath . DIRECTORY_SEPARATOR . 'icon.svg' : false;

        if ($iconPath === false || !is_file($iconPath) || !FileHelper::isSvg($iconPath)) {
            $iconPath = Craft::getAlias('@app/icons/default-plugin.svg');
        }

        return file_get_contents($iconPath);
    }

    /**
     * Returns the license key stored for a given plugin, if it was purchased through the Store.
     *
     * @param string $handle The plugin’s handle
     * @return string|null The plugin’s license key, or null if it isn’t known
     */
    public function getPluginLicenseKey(string $handle)
    {
        return $this->getStoredPluginInfo($handle)['licenseKey'] ?? null;
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
    public function setPluginLicenseKey(string $handle, string $licenseKey = null): bool
    {
        if (($plugin = $this->getPlugin($handle)) === null) {
            throw new InvalidPluginException($handle);
        }

        /** @var Plugin $plugin */
        // Validate the license key
        if ($licenseKey !== null) {
            // Normalize to just uppercase numbers/letters
            $normalizedLicenseKey = mb_strtoupper($licenseKey);
            $normalizedLicenseKey = preg_replace('/[^A-Z0-9]/', '', $normalizedLicenseKey);

            if (strlen($normalizedLicenseKey) != 24) {
                // Invalid key
                throw new InvalidLicenseKeyException($licenseKey);
            }
        } else {
            $normalizedLicenseKey = null;
        }

        Craft::$app->getDb()->createCommand()
            ->update(
                '{{%plugins}}',
                ['licenseKey' => $normalizedLicenseKey],
                ['handle' => $handle])
            ->execute();

        // Update our cache of it
        if (isset($this->_enabledPluginInfo[$handle])) {
            $this->_enabledPluginInfo[$handle]['licenseKey'] = $normalizedLicenseKey;
        }

        // If we've cached the plugin's license key status, update the cache
        if ($this->getPluginLicenseKeyStatus($handle) !== LicenseKeyStatus::Unknown) {
            $this->setPluginLicenseKeyStatus($handle, LicenseKeyStatus::Unknown);
        }

        return true;
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
     * @throws InvalidPluginException if the plugin isn't installed
     */
    public function setPluginLicenseKeyStatus(string $handle, string $licenseKeyStatus = null)
    {
        if (($plugin = $this->getPlugin($handle)) === null) {
            throw new InvalidPluginException($handle);
        }

        /** @var Plugin $plugin */
        Craft::$app->getDb()->createCommand()
            ->update(
                '{{%plugins}}',
                ['licenseKeyStatus' => $licenseKeyStatus],
                ['handle' => $handle])
            ->execute();

        // Update our cache of it
        if (isset($this->_enabledPluginInfo[$handle])) {
            $this->_enabledPluginInfo[$handle]['licenseKeyStatus'] = $licenseKeyStatus;
        }
    }

    // Private Methods
    // =========================================================================

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
                'licenseKey',
                'licenseKeyStatus',
                'settings',
                'installDate'
            ])
            ->from(['{{%plugins}}']);
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
    private function _registerPlugin(PluginInterface $plugin)
    {
        /** @var Plugin $plugin */
        $this->_plugins[$plugin->id] = $plugin;
        Craft::$app->setModule($plugin->id, $plugin);
    }

    /**
     * Unregisters a plugin internally and as an application module.
     *
     * @param PluginInterface $plugin The plugin
     */
    private function _unregisterPlugin(PluginInterface $plugin)
    {
        /** @var Plugin $plugin */
        unset($this->_plugins[$plugin->id]);
        Craft::$app->setModule($plugin->id, null);
    }

    /**
     * Sets the 'migrator' component on a plugin.
     *
     * @param PluginInterface $plugin The plugin
     * @param int $id The plugin’s ID
     */
    private function _setPluginMigrator(PluginInterface $plugin, int $id)
    {
        $ref = new \ReflectionClass($plugin);
        $ns = $ref->getNamespaceName();
        /** @var Plugin $plugin */
        $plugin->set('migrator', [
            'class' => MigrationManager::class,
            'type' => MigrationManager::TYPE_PLUGIN,
            'pluginId' => $id,
            'migrationNamespace' => ($ns ? $ns . '\\' : '') . 'migrations',
            'migrationPath' => $plugin->getBasePath() . DIRECTORY_SEPARATOR . 'migrations',
        ]);
    }

    /**
     * Load
     */
    private function _loadDisabledPluginInfo()
    {
        if ($this->_disabledPluginInfo === null) {
            $this->_disabledPluginInfo = $this->_createPluginQuery()
                ->where(['enabled' => false])
                ->indexBy('handle')
                ->all();
        }
    }
}
