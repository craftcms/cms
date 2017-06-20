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
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use yii\base\Component;
use yii\base\Exception;
use yii\helpers\Inflector;

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
     * @var PluginInterface[] All the installed plugins, indexed by handle
     */
    private $_plugins = [];

    /**
     * @var array|null Plugin info provided by Composer, indexed by the plugins’ lowercase handles
     */
    private $_composerPluginInfo;

    /**
     * @var array|null All of the stored info for installed plugins, indexed by the plugins’ lowercase handles
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

        $path = Craft::$app->getVendorPath().DIRECTORY_SEPARATOR.'craftcms'.DIRECTORY_SEPARATOR.'plugins.php';

        if (file_exists($path)) {
            /** @var array $plugins */
            $plugins = require $path;

            foreach ($plugins as $packageName => $plugin) {
                $plugin['packageName'] = $packageName;
                $lcHandle = strtolower($plugin['handle']);
                $this->_composerPluginInfo[$lcHandle] = $plugin;
            }
        }
    }

    /**
     * Loads the installed plugins.
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
                'settings',
                'installDate'
            ])
            ->from(['{{%plugins}}'])
            ->indexBy('handle')
            ->all();

        foreach ($this->_installedPluginInfo as $lcHandle => &$row) {
            // Clean up the row data
            $row['settings'] = Json::decode($row['settings']);
            $row['installDate'] = DateTimeHelper::toDateTime($row['installDate']);

            $plugin = $this->createPlugin($lcHandle, $row);

            if ($plugin !== null) {
                // If we're not updating, check if the plugin's version number changed, but not its schema version.
                if (!Craft::$app->getIsInMaintenanceMode() && $this->hasPluginVersionNumberChanged($plugin) && !$this->doesPluginRequireDatabaseUpdate($plugin)) {
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
     * Returns an installed plugin by its handle.
     *
     * @param string $handle The plugin’s handle (case-insensitive)
     *
     * @return PluginInterface|null The plugin, or null if it doesn’t exist
     */
    public function getPlugin(string $handle)
    {
        $lcHandle = strtolower($handle);
        $this->loadPlugins();

        if (isset($this->_plugins[$lcHandle])) {
            return $this->_plugins[$lcHandle];
        }

        return null;
    }

    /**
     * Returns an installed plugin by its module ID (its handle converted to kebab-case).
     *
     * @param string $id The plugin’s module ID
     *
     * @return PluginInterface|null The plugin, or null if it doesn’t exist
     */
    public function getPluginByModuleId(string $id)
    {
        $plugin = Craft::$app->getModule($id);

        if ($plugin === null || !$plugin instanceof PluginInterface) {
            return null;
        }

        return $plugin;
    }

    /**
     * Returns an installed plugin by its package name.
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
     * Returns the plugin that contains the given class, if any.
     *
     * @param string $class
     *
     * @return PluginInterface|null The plugin, or null if it can’t be determined.
     */
    public function getPluginByClass(string $class)
    {
        // Figure out the path to the folder that contains this class
        try {
            // Add a trailing slash so we don't get false positives
            $classPath = dirname((new \ReflectionClass($class))->getFileName()).'/';
        } catch (\ReflectionException $e) {
            return null;
        }

        // Find the plugin that contains this path (if any)
        foreach ($this->getAllPlugins() as $plugin) {
            /** @var Plugin $plugin */
            if (StringHelper::startsWith($classPath, $plugin->getBasePath().'/')) {
                return $plugin;
            }
        }

        return null;
    }

    /**
     * Returns all the installed plugins.
     *
     * @return PluginInterface[]
     */
    public function getAllPlugins(): array
    {
        $this->loadPlugins();

        return $this->_plugins;
    }

    /**
     * Installs a plugin by its handle.
     *
     * @param string $handle The plugin’s handle (case-insensitive)
     *
     * @return bool Whether the plugin was installed successfully.
     * @throws Exception if the plugin doesn’t exist
     * @throws \Exception if reasons
     */
    public function installPlugin(string $handle): bool
    {
        $lcHandle = strtolower($handle);
        $this->loadPlugins();

        if (isset($this->_installedPluginInfo[$lcHandle])) {
            // It's already installed
            return true;
        }

        /** @var Plugin $plugin */
        $plugin = $this->createPlugin($lcHandle);

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
                'handle' => $lcHandle,
                'version' => $plugin->getVersion(),
                'schemaVersion' => $plugin->schemaVersion,
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

        $this->_installedPluginInfo[$lcHandle] = $info;
        $this->_registerPlugin($plugin);

        // Fire an 'afterInstallPlugin' event
        $this->trigger(self::EVENT_AFTER_INSTALL_PLUGIN, new PluginEvent([
            'plugin' => $plugin
        ]));

        return true;
    }

    /**
     * Uninstalls a plugin by its handle.
     *
     * @param string $handle The plugin’s handle (case-insensitive)
     *
     * @return bool Whether the plugin was uninstalled successfully
     * @throws Exception if the plugin doesn’t exist
     * @throws \Exception if reasons
     */
    public function uninstallPlugin(string $handle): bool
    {
        $lcHandle = strtolower($handle);
        $this->loadPlugins();

        if (!isset($this->_installedPluginInfo[$lcHandle])) {
            // It's already uninstalled
            return true;
        }

        $plugin = $this->getPlugin($lcHandle);

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
            $id = $this->_installedPluginInfo[$lcHandle]['id'];

            Craft::$app->getDb()->createCommand()
                ->delete('{{%plugins}}', ['id' => $id])
                ->execute();

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        $this->_unregisterPlugin($plugin);
        unset($this->_installedPluginInfo[$lcHandle]);

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
        /** @var Plugin $plugin */
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

        $affectedRows = Craft::$app->getDb()->createCommand()
            ->update(
                '{{%plugins}}',
                ['settings' => Json::encode($plugin->getSettings())],
                ['handle' => strtolower($plugin->handle)])
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
        $lcHandle = $plugin->handle;

        if (isset($this->_installedPluginInfo[$lcHandle])) {
            if ($plugin->getVersion()!== $this->_installedPluginInfo[$lcHandle]['version']) {
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
        $lcHandle = strtolower($plugin->handle);
        $this->loadPlugins();

        if (!isset($this->_installedPluginInfo[$lcHandle])) {
            return false;
        }

        $localVersion = $plugin->schemaVersion;
        $storedVersion = $this->_installedPluginInfo[$lcHandle]['schemaVersion'];

        return version_compare($localVersion, $storedVersion, '>');
    }

    /**
     * Returns the stored info for a given plugin.
     *
     * @param string $handle The plugin handle (case-insensitive)
     *
     * @return array|null The stored info, if there is any
     */
    public function getStoredPluginInfo(string $handle)
    {
        $lcHandle = strtolower($handle);
        $this->loadPlugins();

        if (isset($this->_installedPluginInfo[$lcHandle])) {
            return $this->_installedPluginInfo[$lcHandle];
        }

        return null;
    }

    /**
     * Creates and returns a new plugin instance based on its class handle.
     *
     * @param string     $handle The plugin’s handle (case-insensitive)
     * @param array|null $row    The plugin’s row in the plugins table, if any
     *
     * @return PluginInterface|null
     */
    public function createPlugin(string $handle, array $row = null)
    {
        $lcHandle = strtolower($handle);
        $config = $this->getConfig($lcHandle);

        if (isset($config['aliases'])) {
            foreach ($config['aliases'] as $alias => $path) {
                Craft::setAlias($alias, $path);
            }

            // Unset them so we don't end up calling Module::setAliases()
            unset($config['aliases']);
        }

        // Make sure it was a valid config
        if ($config === null) {
            return null;
        }

        $class = $config['class'];

        // Make sure the class exists and it implements PluginInterface
        if (!is_subclass_of($class, PluginInterface::class)) {
            return null;
        }

        // Set its settings, if it has any (merging DB-stored values with config file values)
        $settings = ArrayHelper::merge(
            $row['settings'] ?? [],
            Craft::$app->getConfig()->getConfigFromFile(strtolower($handle))
        );

        if ($settings !== []) {
            $config['settings'] = $settings;
        }

        // Create the plugin
        /** @var Plugin $plugin */
        $moduleId = Inflector::camel2id($config['handle']);
        $plugin = Craft::createObject($config, [$moduleId, Craft::$app]);

        if (isset($row['id'])) {
            $this->_setPluginMigrator($plugin, $row['id']);
        }

        return $plugin;
    }

    /**
     * Returns the config array for a plugin, based on its handle.
     *
     * @param string $handle The plugin’s handle (case-insensitive)
     *
     * @return array|null The plugin’s config, if it can be determined
     */
    public function getConfig(string $handle)
    {
        $lcHandle = strtolower($handle);
        $config = $this->_composerPluginInfo[$lcHandle] ?? null;

        // Make sure it's valid
        if (!$this->validateConfig($config)) {
            Craft::warning("Missing 'class', 'name', or 'version' keys for plugin \"{$handle}\".", __METHOD__);

            return null;
        }

        return $config;
    }

    /**
     * Validates a plugin's config by ensuring it has a valid class, name, and version
     *
     * @param array|null &$config
     *
     * @return bool Whether the config validates.
     */
    public function validateConfig(array &$config = null): bool
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
     * Returns info about all of the plugins we can find, whether they’re installed or not.
     *
     * @return array
     */
    public function getAllPluginInfo()
    {
        $this->loadPlugins();

        // Get all the plugin handles
        $lcHandles = array_keys($this->_composerPluginInfo);

        // Get the info arrays
        $info = [];
        $names = [];

        foreach ($lcHandles as $lcHandle) {
            $lcHandle = strtolower($lcHandle);
            $config = $this->getConfig($lcHandle);

            // Skip if it doesn't have a valid config file
            if ($config === null) {
                continue;
            }

            /** @var Plugin|null $plugin */
            $plugin = $this->getPlugin($lcHandle);

            $config['isInstalled'] = isset($this->_installedPluginInfo[$lcHandle]);
            $config['moduleId'] = $plugin !== null ? $plugin->id : Inflector::camel2id($config['handle']);
            $config['hasCpSettings'] = ($plugin !== null && $plugin->hasCpSettings);

            $info[$lcHandle] = $config;
            $names[] = $config['name'];
        }

        // Sort plugins by their names
        array_multisort($names, SORT_NATURAL | SORT_FLAG_CASE, $info);

        return $info;
    }

    /**
     * Returns a plugin’s SVG icon.
     *
     * @param string $handle The plugin’s class handle (case-insensitive)
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

        $iconPath = ($basePath !== false) ? $basePath.DIRECTORY_SEPARATOR.'icon.svg' : false;

        if ($iconPath === false || !is_file($iconPath) || FileHelper::getMimeType($iconPath) !== 'image/svg+xml') {
            $iconPath = Craft::getAlias('@app/icons/default-plugin.svg');
        }

        return file_get_contents($iconPath);
    }

    /**
     * Returns the license key stored for a given plugin, if it was purchased through the Store.
     *
     * @param string $handle The plugin’s class handle (case-insensitive)
     *
     * @return string|null The plugin’s license key, or null if it isn’t known
     * @throws InvalidPluginException if the plugin isn't installed
     */
    public function getPluginLicenseKey(string $handle)
    {
        $lcHandle = $handle;
        $plugin = $this->getPlugin($lcHandle);

        if (!$plugin) {
            throw new InvalidPluginException($handle);
        }

        if (isset($this->_installedPluginInfo[$lcHandle]['licenseKey'])) {
            return $this->_installedPluginInfo[$lcHandle]['licenseKey'];
        }

        return null;
    }

    /**
     * Sets a plugin’s license key.
     *
     * Note this should *not* be used to store license keys generated by third party stores.
     *
     * @param string      $handle     The plugin’s handle (case-insensitive)
     * @param string|null $licenseKey The plugin’s license key
     *
     * @return bool Whether the license key was updated successfully
     *
     * @throws InvalidPluginException if the plugin isn't installed
     * @throws InvalidLicenseKeyException if $licenseKey is invalid
     */
    public function setPluginLicenseKey(string $handle, string $licenseKey = null): bool
    {
        $lcHandle = strtolower($handle);
        $plugin = $this->getPlugin($lcHandle);

        if (!$plugin) {
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
                ['handle' => $lcHandle])
            ->execute();

        // Update our cache of it
        if (isset($this->_installedPluginInfo[$lcHandle])) {
            $this->_installedPluginInfo[$lcHandle]['licenseKey'] = $normalizedLicenseKey;
        }

        // If we've cached the plugin's license key status, update the cache
        if ($this->getPluginLicenseKeyStatus($lcHandle) !== LicenseKeyStatus::Unknown) {
            $this->setPluginLicenseKeyStatus($lcHandle, LicenseKeyStatus::Unknown);
        }

        return true;
    }

    /**
     * Returns the license key status of a given plugin.
     *
     * @param string $handle The plugin’s handle (case-insensitive)
     *
     * @return string|false
     * @throws InvalidPluginException if the plugin isn't installed
     */
    public function getPluginLicenseKeyStatus(string $handle)
    {
        $lcHandle = strtolower($handle);
        $plugin = $this->getPlugin($lcHandle);

        if (!$plugin) {
            throw new InvalidPluginException($handle);
        }

        if (isset($this->_installedPluginInfo[$lcHandle]['licenseKeyStatus'])) {
            return $this->_installedPluginInfo[$lcHandle]['licenseKeyStatus'];
        }

        return LicenseKeyStatus::Unknown;
    }

    /**
     * Sets the license key status for a given plugin.
     *
     * @param string      $handle           The plugin’s handle (case-insensitive)
     * @param string|null $licenseKeyStatus The plugin’s license key status
     *
     * @return void
     * @throws InvalidPluginException if the plugin isn't installed
     */
    public function setPluginLicenseKeyStatus(string $handle, string $licenseKeyStatus = null)
    {
        $lcHandle = strtolower($handle);
        $plugin = $this->getPlugin($lcHandle);

        if (!$plugin) {
            throw new InvalidPluginException($handle);
        }

        /** @var Plugin $plugin */
        Craft::$app->getDb()->createCommand()
            ->update(
                '{{%plugins}}',
                ['licenseKeyStatus' => $licenseKeyStatus],
                ['handle' => $lcHandle])
            ->execute();

        // Update our cache of it
        if (isset($this->_installedPluginInfo[$lcHandle])) {
            $this->_installedPluginInfo[$lcHandle]['licenseKeyStatus'] = $licenseKeyStatus;
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * Registers a plugin internally and as an application module.
     *
     * This should only be called for installed plugins
     *
     * @param PluginInterface $plugin The plugin
     */
    private function _registerPlugin(PluginInterface $plugin)
    {
        /** @var Plugin $plugin */
        $lcHandle = strtolower($plugin->handle);
        $this->_plugins[$lcHandle] = $plugin;
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
        $lcHandle = strtolower($plugin->handle);
        unset($this->_plugins[$lcHandle]);
        Craft::$app->setModule($plugin->id, null);
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
}
