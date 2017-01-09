<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
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
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use yii\base\Component;
use yii\base\Exception;

/**
 * The Plugins service provides APIs for managing plugins.
 *
 * An instance of the Plugins service is globally accessible in Craft via [[Application::plugins `Craft::$app->getPlugins()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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
     * @var PluginInterface[] All the enabled plugins
     */
    private $_plugins = [];

    /**
     * @var array Info for Composer-installed plugins, indexed by the plugins’ handles
     */
    private $_composerPluginInfo;

    /**
     * @var array All of the stored info for enabled plugins, indexed by the plugins’ handles
     */
    private $_installedPluginInfo;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->_composerPluginInfo = [];

        if (App::isComposerInstall()) {
            // See if any plugins were installed via Composer, too
            $path = Craft::$app->getVendorPath().DIRECTORY_SEPARATOR.'craftcms'.DIRECTORY_SEPARATOR.'plugins.php';

            if (file_exists($path)) {
                $plugins = require $path;

                foreach ($plugins as $packageName => $plugin) {
                    $handle = strtolower($plugin['handle']);
                    unset($plugin['handle']);
                    $plugin['packageName'] = $packageName;
                    $this->_composerPluginInfo[$handle] = $plugin;
                }
            }
        }
    }

    /**
     * Loads the enabled plugins.
     *
     * @return void
     */
    public function loadPlugins()
    {
        if ($this->_pluginsLoaded === true || $this->_loadingPlugins === true || Craft::$app->getIsInstalled() === false || Craft::$app->getIsUpdating() === true) {
            return;
        }

        // Prevent this function from getting called twice.
        $this->_loadingPlugins = true;

        // Fire a 'beforeLoadPlugins' event
        $this->trigger(self::EVENT_BEFORE_LOAD_PLUGINS);

        // Find all of the installed plugins
        $this->_installedPluginInfo = (new Query())
            ->select([
                'id',
                'handle',
                'version',
                'schemaVersion',
                'licenseKey',
                'licenseKeyStatus',
                'enabled',
                'settings',
                'installDate'
            ])
            ->from(['{{%plugins}}'])
            ->indexBy('handle')
            ->all();

        foreach ($this->_installedPluginInfo as $handle => &$row) {
            // Clean up the row data
            $row['enabled'] = (bool)$row['enabled'];
            $row['settings'] = Json::decode($row['settings']);
            $row['installDate'] = DateTimeHelper::toDateTime($row['installDate']);

            // Skip disabled plugins
            if ($row['enabled'] !== true) {
                continue;
            }

            $plugin = $this->createPlugin($handle, $row);

            if ($plugin !== null) {
                $this->_registerPlugin($handle, $plugin);
            }
        }
        unset($row);

        $this->_loadingPlugins = false;
        $this->_pluginsLoaded = true;

        // Fire an 'afterLoadPlugins' event
        $this->trigger(self::EVENT_AFTER_LOAD_PLUGINS);
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
     *
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
     *
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
     *
     * @return bool Whether the plugin was enabled successfully
     * @throws InvalidPluginException if the plugin isn't installed
     */
    public function enablePlugin(string $handle): bool
    {
        $this->loadPlugins();

        if (!isset($this->_installedPluginInfo[$handle])) {
            throw new InvalidPluginException($handle);
        }

        if ($this->_installedPluginInfo[$handle]['enabled'] === true) {
            // It's already enabled
            return true;
        }

        $plugin = $this->createPlugin($handle, $this->_installedPluginInfo[$handle]);

        if ($plugin === null) {
            throw new InvalidPluginException($handle);
        }

        // Fire a 'beforeEnablePlugin' event
        $this->trigger(self::EVENT_BEFORE_ENABLE_PLUGIN, new PluginEvent([
            'plugin' => $plugin
        ]));

        Craft::$app->getDb()->createCommand()
            ->update(
                '{{%plugins}}',
                ['enabled' => '1'],
                ['handle' => $handle])
            ->execute();

        $this->_installedPluginInfo[$handle]['enabled'] = true;
        $this->_registerPlugin($handle, $plugin);

        // Fire an 'afterEnablePlugin' event
        $this->trigger(self::EVENT_AFTER_ENABLE_PLUGIN, new PluginEvent([
            'plugin' => $plugin
        ]));

        return true;
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
        $this->loadPlugins();

        if (!isset($this->_installedPluginInfo[$handle])) {
            throw new InvalidPluginException($handle);
        }

        if ($this->_installedPluginInfo[$handle]['enabled'] === false) {
            // It's already disabled
            return true;
        }

        $plugin = $this->getPlugin($handle);

        if ($plugin === null) {
            throw new InvalidPluginException($handle);
        }

        // Fire a 'beforeDisablePlugin' event
        $this->trigger(self::EVENT_BEFORE_DISABLE_PLUGIN, new PluginEvent([
            'plugin' => $plugin
        ]));

        Craft::$app->getDb()->createCommand()
            ->update(
                '{{%plugins}}',
                ['enabled' => '0'],
                ['handle' => $handle])
            ->execute();

        $this->_installedPluginInfo[$handle]['enabled'] = false;
        $this->_unregisterPlugin($handle);

        // Fire an 'afterDisablePlugin' event
        $this->trigger(self::EVENT_AFTER_DISABLE_PLUGIN, new PluginEvent([
            'plugin' => $plugin
        ]));

        return true;
    }

    /**
     * Installs a plugin by its handle.
     *
     * @param string $handle The plugin’s handle
     *
     * @return bool Whether the plugin was installed successfully.
     * @throws Exception if the plugin doesn’t exist
     * @throws \Exception if reasons
     */
    public function installPlugin(string $handle): bool
    {
        $this->loadPlugins();

        if (isset($this->_installedPluginInfo[$handle])) {
            // It's already installed
            return true;
        }

        /** @var Plugin $plugin */
        $plugin = $this->createPlugin($handle);

        if ($plugin === null) {
            throw new InvalidPluginException($handle);
        }

        // Fire a 'beforeInstallPlugin' event
        $this->trigger(self::EVENT_BEFORE_INSTALL_PLUGIN, new PluginEvent([
            'plugin' => $plugin
        ]));

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $info = [
                'handle' => $handle,
                'version' => $plugin->version,
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
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        $this->_installedPluginInfo[$handle] = $info;
        $this->_registerPlugin($handle, $plugin);

        // Fire an 'afterInstallPlugin' event
        $this->trigger(self::EVENT_AFTER_INSTALL_PLUGIN, new PluginEvent([
            'plugin' => $plugin
        ]));

        return true;
    }

    /**
     * Uninstalls a plugin by its handle.
     *
     * @param string $handle The plugin’s handle
     *
     * @return bool Whether the plugin was uninstalled successfully
     * @throws Exception if the plugin doesn’t exist
     * @throws \Exception if reasons
     */
    public function uninstallPlugin(string $handle): bool
    {
        $this->loadPlugins();

        if (!isset($this->_installedPluginInfo[$handle])) {
            // It's already uninstalled
            return true;
        }

        // Is it enabled?
        if ($this->_installedPluginInfo[$handle]['enabled'] === true) {
            $plugin = $this->getPlugin($handle);
        } else {
            $plugin = $this->createPlugin($handle, $this->_installedPluginInfo[$handle]);
        }

        if ($plugin === null) {
            throw new InvalidPluginException($handle);
        }

        // Fire a 'beforeUninstallPlugin' event
        $this->trigger(self::EVENT_BEFORE_UNINSTALL_PLUGIN, new PluginEvent([
            'plugin' => $plugin
        ]));

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Let the plugin uninstall itself first
            if ($plugin->uninstall() === false) {
                $transaction->rollBack();

                return false;
            }

            // Clean up the plugins and migrations tables
            $id = $this->_installedPluginInfo[$handle]['id'];

            Craft::$app->getDb()->createCommand()
                ->delete('{{%plugins}}', ['id' => $id])
                ->execute();

            Craft::$app->getDb()->createCommand()
                ->delete('{{%migrations}}', ['pluginId' => $id])
                ->execute();

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        $this->_unregisterPlugin($handle);
        unset($this->_installedPluginInfo[$handle]);

        // Fire an 'afterUninstallPlugin' event
        $this->trigger(self::EVENT_AFTER_UNINSTALL_PLUGIN, new PluginEvent([
            'plugin' => $plugin
        ]));

        return true;
    }

    /**
     * Saves a plugin's settings.
     *
     * @param PluginInterface $plugin   The plugin
     * @param array           $settings The plugin’s new settings
     *
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
        $this->trigger(self::EVENT_BEFORE_SAVE_PLUGIN_SETTINGS, new PluginEvent([
            'plugin' => $plugin
        ]));

        // JSON-encode them and save the plugin row
        $jsSettings = Json::encode($plugin->getSettings());

        $affectedRows = Craft::$app->getDb()->createCommand()
            ->update(
                '{{%plugins}}',
                ['settings' => $jsSettings],
                ['handle' => $plugin->getHandle()])
            ->execute();

        // Fire an 'afterSavePluginSettings' event
        $this->trigger(self::EVENT_AFTER_SAVE_PLUGIN_SETTINGS, new PluginEvent([
            'plugin' => $plugin
        ]));

        return (bool)$affectedRows;
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
        /** @var Plugin $plugin */
        $this->loadPlugins();
        $handle = $plugin->getHandle();

        if (isset($this->_installedPluginInfo[$handle])) {
            if ($plugin->version != $this->_installedPluginInfo[$handle]['version']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether the given plugin’s local schema version is greater than the record we have in the database.
     *
     * @param PluginInterface $plugin The plugin
     *
     * @return bool Whether the plugin’s local schema version is greater than the record we have in the database
     */
    public function doesPluginRequireDatabaseUpdate(PluginInterface $plugin): bool
    {
        /** @var Plugin $plugin */
        $this->loadPlugins();
        $handle = $plugin->getHandle();

        if (!isset($this->_installedPluginInfo[$handle])) {
            return false;
        }

        $localVersion = $plugin->schemaVersion;
        $storedVersion = $this->_installedPluginInfo[$handle]['schemaVersion'];

        return version_compare($localVersion, $storedVersion, '>');
    }

    /**
     * Returns the stored info for a given plugin.
     *
     * @param string $handle The plugin handle
     *
     * @return array|null The stored info, if there is any
     */
    public function getStoredPluginInfo(string $handle)
    {
        $this->loadPlugins();

        if (isset($this->_installedPluginInfo[$handle])) {
            return $this->_installedPluginInfo[$handle];
        }

        return null;
    }

    /**
     * Creates and returns a new plugin instance based on its class handle.
     *
     * @param string     $handle The plugin’s handle
     * @param array|null $row    The plugin’s row in the plugins table, if any
     *
     * @return PluginInterface|null
     */
    public function createPlugin(string $handle, array $row = null)
    {
        $config = $this->getConfig($handle);

        if (isset($config['aliases'])) {
            foreach ($config['aliases'] as $alias => $path) {
                Craft::setAlias($alias, $path);
            }
        }

        // Make sure it was a valid config
        if ($config === null) {
            return null;
        }

        // If the plugin was manually installed, see if it has a Composer autoloader
        if (!isset($this->_composerPluginInfo[$handle])) {
            $autoloadPath = Craft::$app->getPath()->getPluginsPath().DIRECTORY_SEPARATOR.$handle.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';
            if (is_file($autoloadPath)) {
                require_once $autoloadPath;
            }
        }

        $class = $config['class'];

        // Make sure the class exists and it implements PluginInterface
        if (!is_subclass_of($class, PluginInterface::class)) {
            return null;
        }

        // Create the plugin
        /** @var Plugin $plugin */
        $plugin = Craft::createObject($config, [$handle, Craft::$app]);

        // Set its settings
        if (isset($row['settings'])) {
            $plugin->getSettings()->setAttributes($row['settings'], false);
        }

        if (isset($row['id'])) {
            $this->_setPluginMigrator($plugin, $row['id']);
        }

        // If we're not updating, check if the plugin's version number changed, but not its schema version.
        if (!Craft::$app->getIsInMaintenanceMode() && $this->hasPluginVersionNumberChanged($plugin) && !$this->doesPluginRequireDatabaseUpdate($plugin)) {
            // Update our record of the plugin's version number
            Craft::$app->getDb()->createCommand()
                ->update(
                    '{{%plugins}}',
                    ['version' => $plugin->version],
                    ['id' => $row['id']])
                ->execute();
        }

        return $plugin;
    }

    /**
     * Returns the config array for a plugin, based on its handle.
     *
     * @param string $handle The plugin’s handle
     *
     * @return array|null The plugin’s config, if it can be determined
     */
    public function getConfig(string $handle)
    {
        // Was this plugin installed via Composer?
        if (isset($this->_composerPluginInfo[$handle])) {
            $config = $this->_composerPluginInfo[$handle];
        } else {
            $config = $this->_scrapeConfigFromComposerJson($handle);
        }

        // Make sure it's valid
        if (!$this->validateConfig($config)) {
            Craft::warning("Missing 'class', 'name', or 'version' keys for plugin \"{$handle}\".");

            return null;
        }

        return $config;
    }

    /**
     * Validates a plugin's config by ensuring it has a valid class, name, and version
     *
     * @param array &$config
     *
     * @return bool Whether the config validates.
     */
    public function validateConfig(array &$config): bool
    {
        // Make sure it has the essentials
        if (!is_array($config) || !isset($config['class'], $config['name'], $config['version'])) {
            return false;
        }

        // Add any missing properties
        $config = array_merge([
            'developer' => null,
            'developerUrl' => null,
            'description' => null,
            'documentationUrl' => null,
            'schemaVersion' => '1.0.0',
        ], $config);

        return true;
    }

    /**
     * Returns info about all of the plugins saved in craft/plugins, whether they’re installed or not.
     *
     * @return array Info about all of the plugins saved in craft/plugins
     */
    public function getAllPluginInfo()
    {
        $this->loadPlugins();

        // Get all the plugin handles
        $handles = array_unique(array_merge(
            array_keys($this->_composerPluginInfo),
            $this->_getManualPluginHandles()
        ));

        // Get the info arrays
        $info = [];
        $names = [];

        foreach ($handles as $handle) {
            $config = $this->getConfig($handle);

            // Skip if it doesn't have a valid config file
            if ($config === null) {
                continue;
            }

            $plugin = $this->getPlugin($handle);

            $config['isInstalled'] = isset($this->_installedPluginInfo[$handle]);
            $config['isEnabled'] = ($plugin !== null);
            $config['hasSettings'] = ($plugin !== null && $plugin->getSettings() !== null);

            $info[$handle] = $config;
            $names[] = $config['name'];
        }

        // Sort plugins by their names
        array_multisort($names, SORT_NATURAL | SORT_FLAG_CASE, $info);

        return $info;
    }

    /**
     * Returns a plugin’s SVG icon.
     *
     * @param string $handle The plugin’s class handle
     *
     * @return string The given plugin’s SVG icon
     * @throws InvalidPluginException if the plugin isn't installed
     */
    public function getPluginIconSvg(string $handle): string
    {
        if (($plugin = $this->getPlugin($handle)) !== null) {
            /** @var Plugin $plugin */
            $basePath = $plugin->getBasePath();
        } else if (($config = $this->getConfig($handle)) !== null) {
            // It exists, but isn't installed yet
            $basePath = isset($config['basePath']) ? Craft::getAlias($config['basePath']) : false;
        } else {
            throw new InvalidPluginException($handle);
        }

        $iconPath = ($basePath !== false) ? $basePath.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'icon.svg' : false;

        if ($iconPath === false || !is_file($iconPath) || FileHelper::getMimeType($iconPath) !== 'image/svg+xml') {
            $iconPath = Craft::$app->getPath()->getResourcesPath().DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'default_plugin.svg';
        }

        return file_get_contents($iconPath);
    }

    /**
     * Returns the license key stored for a given plugin, if it was purchased through the Store.
     *
     * @param string $pluginHandle The plugin’s class handle
     *
     * @return string|null The plugin’s license key, or null if it isn’t known
     * @throws InvalidPluginException if the plugin isn't installed
     */
    public function getPluginLicenseKey(string $pluginHandle)
    {
        $plugin = $this->getPlugin($pluginHandle);

        if (!$plugin) {
            throw new InvalidPluginException($pluginHandle);
        }

        if (isset($this->_installedPluginInfo[$pluginHandle]['licenseKey'])) {
            return $this->_installedPluginInfo[$pluginHandle]['licenseKey'];
        }

        return null;
    }

    /**
     * Sets a plugin’s license key.
     *
     * Note this should *not* be used to store license keys generated by third party stores.
     *
     * @param string      $pluginHandle The plugin’s class handle
     * @param string|null $licenseKey   The plugin’s license key
     *
     * @return bool Whether the license key was updated successfully
     *
     * @throws InvalidPluginException if the plugin isn't installed
     * @throws InvalidLicenseKeyException if $licenseKey is invalid
     */
    public function setPluginLicenseKey(string $pluginHandle, string $licenseKey = null): bool
    {
        $plugin = $this->getPlugin($pluginHandle);

        if (!$plugin) {
            throw new InvalidPluginException($pluginHandle);
        }

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

        // Ignore the plugin handle they sent us in case its casing is wrong
        $pluginHandle = $plugin->getHandle();

        Craft::$app->getDb()->createCommand()
            ->update(
                '{{%plugins}}',
                ['licenseKey' => $normalizedLicenseKey],
                ['handle' => $pluginHandle])
            ->execute();

        // Update our cache of it if the plugin is enabled
        if (isset($this->_installedPluginInfo[$pluginHandle])) {
            $this->_installedPluginInfo[$pluginHandle]['licenseKey'] = $normalizedLicenseKey;
        }

        // If we've cached the plugin's license key status, update the cache
        if ($this->getPluginLicenseKeyStatus($pluginHandle) !== LicenseKeyStatus::Unknown) {
            $this->setPluginLicenseKeyStatus($pluginHandle, LicenseKeyStatus::Unknown);
        }

        return true;
    }

    /**
     * Returns the license key status of a given plugin.
     *
     * @param string $pluginHandle The plugin’s class handle
     *
     * @return string|false
     * @throws InvalidPluginException if the plugin isn't installed
     */
    public function getPluginLicenseKeyStatus(string $pluginHandle)
    {
        $plugin = $this->getPlugin($pluginHandle);

        if (!$plugin) {
            throw new InvalidPluginException($pluginHandle);
        }

        if (isset($this->_installedPluginInfo[$pluginHandle]['licenseKeyStatus'])) {
            return $this->_installedPluginInfo[$pluginHandle]['licenseKeyStatus'];
        }

        return LicenseKeyStatus::Unknown;
    }

    /**
     * Sets the license key status for a given plugin.
     *
     * @param string      $pluginHandle     The plugin’s class handle
     * @param string|null $licenseKeyStatus The plugin’s license key status
     *
     * @return void
     * @throws InvalidPluginException if the plugin isn't installed
     */
    public function setPluginLicenseKeyStatus(string $pluginHandle, string $licenseKeyStatus = null)
    {
        $plugin = $this->getPlugin($pluginHandle);

        if (!$plugin) {
            throw new InvalidPluginException($pluginHandle);
        }

        // Ignore the plugin handle they sent us in case its casing is wrong
        $pluginHandle = $plugin->getHandle();

        Craft::$app->getDb()->createCommand()
            ->update(
                '{{%plugins}}',
                ['licenseKeyStatus' => $licenseKeyStatus],
                ['handle' => $pluginHandle])
            ->execute();

        // Update our cache of it if the plugin is enabled
        if (isset($this->_installedPluginInfo[$pluginHandle])) {
            $this->_installedPluginInfo[$pluginHandle]['licenseKeyStatus'] = $licenseKeyStatus;
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * Registers a plugin internally and as an application module.
     *
     * This should only be called for enabled plugins
     *
     * @param string          $handle The plugin’s handle
     * @param PluginInterface $plugin The plugin
     */
    private function _registerPlugin(string $handle, PluginInterface $plugin)
    {
        /** @var Plugin $plugin */
        $plugin::setInstance($plugin);
        $this->_plugins[$handle] = $plugin;
        Craft::$app->setModule($handle, $plugin);
    }

    /**
     * Unregisters a plugin internally and as an application module.
     *
     * @param string $handle The plugin’s handle
     */
    private function _unregisterPlugin(string $handle)
    {
        unset($this->_plugins[$handle]);
        Craft::$app->setModule($handle, null);
    }

    /**
     * Sets the 'migrator' component on a plugin.
     *
     * @param PluginInterface $plugin The plugin
     * @param int             $id     The plugin’s ID
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
            'migrationNamespace' => ($ns ? $ns.'\\' : '').'migrations',
            'migrationPath' => $plugin->getBasePath().DIRECTORY_SEPARATOR.'migrations',
        ]);
    }

    /**
     * Returns an array of folder names that live within the craft/plugins/ folder.
     *
     * @return string[]
     * @throws Exception in case of failure
     */
    private function _getManualPluginHandles(): array
    {
        $dir = Craft::$app->getPath()->getPluginsPath();
        if (!is_dir($dir)) {
            return [];
        }

        $pluginHandles = [];
        $handle = opendir($dir);
        if ($handle === false) {
            throw new Exception("Unable to open directory: $dir");
        }
        while (($subDir = readdir($handle)) !== false) {
            if ($subDir === '.' || $subDir === '..') {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$subDir;
            if (is_file($path)) {
                continue;
            }
            $pluginHandles[] = $subDir;
        }
        closedir($handle);

        return $pluginHandles;
    }

    /**
     * Scrapes a plugin’s config from its composer.json file.
     *
     * @param string $handle The plugin’s handle
     *
     * @return array|null The plugin’s config, if it can be determined
     */
    private function _scrapeConfigFromComposerJson(string $handle)
    {
        // Make sure this plugin has a composer.json file
        $pluginPath = Craft::$app->getPath()->getPluginsPath().DIRECTORY_SEPARATOR.$handle;
        $composerPath = $pluginPath.DIRECTORY_SEPARATOR.'composer.json';

        if (!is_file($composerPath)) {
            Craft::warning("Could not find a composer.json file for the plugin '$handle'.");

            return null;
        }

        try {
            $composer = Json::decode(file_get_contents($composerPath));
        } catch (\Exception $e) {
            Craft::warning("Could not decode {$composerPath}: ".$e->getMessage());

            return null;
        }

        // Validate the package name
        if (!isset($composer['name']) || strpos($composer['name'], '/') === false) {
            Craft::warning("Invalid package name in composer.json for the plugin '$handle'".(isset($composer['name']) ? ': '.$composer['name'] : '').'.');

            return null;
        }

        list($vendor, $name) = explode('/', $composer['name'], 2);
        $extra = $composer['extra'] ?? [];

        // class (required) + basePath + possibly set aliases
        $class = $extra['class'] ?? null;
        $basePath = $extra['basePath'] ?? null;
        $aliases = $this->_generateDefaultAliasesFromComposer($handle, $composer, $class, $basePath);

        if ($class === null) {
            Craft::warning("Unable to determine the Plugin class for {$handle}.");

            return null;
        }

        $config = [
            'packageName' => $composer['name'],
            'class' => $class,
        ];

        if ($basePath !== null) {
            $config['basePath'] = $basePath;
        }

        if ($aliases) {
            $config['aliases'] = $aliases;
        }

        // name
        if (isset($extra['name'])) {
            $config['name'] = $extra['name'];
        } else {
            $config['name'] = $name;
        }

        // version
        if (isset($extra['version'])) {
            $config['version'] = $extra['version'];
        } else if (isset($composer['version'])) {
            $config['version'] = $composer['version'];
        } else {
            // Might as well be consistent with what Composer will default to
            $config['version'] = 'dev-master';
        }

        // schemaVersion
        if (isset($extra['schemaVersion'])) {
            $config['schemaVersion'] = $extra['schemaVersion'];
        }

        // description
        if (isset($extra['description'])) {
            $config['description'] = $extra['description'];
        } else if (isset($composer['description'])) {
            $config['description'] = $composer['description'];
        }

        // developer
        if (isset($extra['developer'])) {
            $config['developer'] = $extra['developer'];
        } else if ($authorName = $this->_getAuthorPropertyFromComposer($composer, 'name')) {
            $config['developer'] = $authorName;
        } else {
            $config['developer'] = $vendor;
        }

        // developerUrl
        if (isset($extra['developerUrl'])) {
            $config['developerUrl'] = $extra['developerUrl'];
        } else if (isset($composer['homepage'])) {
            $config['developerUrl'] = $composer['homepage'];
        } else if ($authorHomepage = $this->_getAuthorPropertyFromComposer($composer, 'homepage')) {
            $config['developerUrl'] = $authorHomepage;
        }

        // documentationUrl
        if (isset($extra['documentationUrl'])) {
            $config['documentationUrl'] = $extra['documentationUrl'];
        } else if (isset($composer['support']['docs'])) {
            $config['documentationUrl'] = $composer['support']['docs'];
        }

        // components
        if (isset($extra['components'])) {
            $config['components'] = $extra['components'];
        }

        return $config;
    }

    /**
     * Returns an array of alias/path mappings that should be set for a plugin based on its Composer config.
     *
     * It will also set the $class variable to the primary Plugin class, if it can isn't set already and the class can be found.
     *
     * @param string      $handle    The plugin handle
     * @param array       $composer  The Composer config
     * @param string|null &$class    The Plugin class name
     * @param string|null &$basePath The plugin's base path
     *
     * @return array|null
     */
    private function _generateDefaultAliasesFromComposer(string $handle, array $composer, &$class = null, &$basePath = null)
    {
        if (empty($composer['autoload']['psr-4'])) {
            return null;
        }

        $aliases = [];

        foreach ($composer['autoload']['psr-4'] as $namespace => $path) {
            if (is_array($path)) {
                // Yii doesn't support aliases that point to multiple base paths
                continue;
            }

            // Normalize $path to an absolute path
            $path = FileHelper::normalizePath($path);
            if ((!isset($path[0]) || $path[0] !== DIRECTORY_SEPARATOR) && (!isset($path[1]) || $path[1] !== ':')) {
                $pluginPath = Craft::$app->getPath()->getPluginsPath().DIRECTORY_SEPARATOR.$handle;
                $path = $pluginPath.DIRECTORY_SEPARATOR.$path;
            }

            $alias = '@'.str_replace('\\', '/', trim($namespace, '\\'));
            $aliases[$alias] = $path;

            // If we're still looking for the primary Plugin class, see if it's in here
            if ($class === null && is_file($path.DIRECTORY_SEPARATOR.'Plugin.php')) {
                $class = $namespace.'Plugin';
            }

            // If we're still looking for the base path but we know the primary Plugin class,
            // see if the class namespace matches up, and the file is in here.
            // If so, set the base path to whatever directory contains the plugin class.
            if ($basePath === null && $class !== null) {
                if (strpos($class, $namespace) === 0) {
                    $testClassPath = $path.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($namespace))).'.php';
                    if (file_exists($testClassPath)) {
                        $basePath = dirname($testClassPath);
                    }
                }
            }
        }

        return $aliases;
    }

    /**
     * Attempts to return an author property from a given composer.json file.
     *
     * @param array  $composer
     * @param string $property
     *
     * @return string|null
     */
    private function _getAuthorPropertyFromComposer(array $composer, string $property)
    {
        if (empty($composer['authors'])) {
            return null;
        }

        $firstAuthor = ArrayHelper::firstValue($composer['authors']);

        if (!isset($firstAuthor[$property])) {
            return null;
        }

        return $firstAuthor[$property];
    }
}
