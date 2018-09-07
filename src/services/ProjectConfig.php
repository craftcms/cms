<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\Plugin;
use craft\base\PluginTrait;
use craft\events\ParseConfigEvent;
use craft\helpers\DateTimeHelper;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\Path as PathHelper;
use Symfony\Component\Yaml\Yaml;
use yii\base\Application;
use yii\base\Component;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\web\ServerErrorHttpException;

/**
 * Project config service.
 * An instance of the ProjectConfig service is globally accessible in Craft via [[\craft\base\ApplicationTrait::ProjectConfig()|`Craft::$app->projectConfig`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1
 */
class ProjectConfig extends Component
{
    // Constants
    // =========================================================================

    // Cache settings
    // -------------------------------------------------------------------------

    const CACHE_KEY = 'project.config.files';
    const CACHE_DURATION = 2592000; // 30 days

    // Array key to use if not using config files.
    const CONFIG_KEY = 'storedConfig';

    // Filename for base config file
    const CONFIG_FILENAME = 'project.yaml';

    // Key to use for schema version storage.
    const CONFIG_SCHEMA_VERSION_KEY = 'schemaVersion';

    // TODO move this to UID validator class
    // TODO update StringHelper::isUUID() to use that
    // Regexp patterns
    // -------------------------------------------------------------------------

    const UID_PATTERN = '[a-zA-Z0-9_-]+';

    // Events
    // -------------------------------------------------------------------------

    /**
     * @event ParseConfigEvent The event that is triggered on encountering a new config object
     *
     * Components can get notified when a new config object is encountered
     *
     * ```php
     * use craft\events\ParseConfigEvent;
     * use craft\services\services\ProjectConfig;
     * use yii\base\Event;
     *
     * Event::on(ProjectConfig::class, ProjectConfig::EVENT_NEW_CONFIG_OBJECT, function(ParseConfigEvent $e) {
     *      // Do something with the new configuration info
     * });
     * ```
     */
    const EVENT_NEW_CONFIG_OBJECT = 'newConfigObject';

    /**
     * @event ParseConfigEvent The event that is triggered on encountering a changed config object
     *
     * Components can get notified when changes in a config object are encountered
     *
     * ```php
     * use craft\events\ParseConfigEvent;
     * use craft\services\services\ProjectConfig;
     * use yii\base\Event;
     *
     * Event::on(ProjectConfig::class, ProjectConfig::EVENT_CHANGED_CONFIG_OBJECT, function(ParseConfigEvent $e) {
     *      // Do something with the changed configuration info
     * });
     * ```
     */
    const EVENT_CHANGED_CONFIG_OBJECT = 'changedConfigObject';

    /**
     * @event ParseConfigEvent The event that is triggered on encountering a removed config object
     *
     * Components can get notified when a config object is removed
     *
     * ```php
     * use craft\events\ParseConfigEvent;
     * use craft\services\services\ProjectConfig;
     * use yii\base\Event;
     *
     * Event::on(ProjectConfig::class, ProjectConfig::EVENT_REMOVED_CONFIG_OBJECT, function(ParseConfigEvent $e) {
     *      // Do something with the information that a configuration object was removed
     * });
     * ```
     */
    const EVENT_REMOVED_CONFIG_OBJECT = 'removedConfigObject';

    /**
     * @event ParseConfigEvent The event that is triggered after parsing all configuration changes
     *
     * Components can get notified when all configuration has been parsed
     *
     * ```php
     * use craft\events\ParseConfigEvent;
     * use craft\services\services\ProjectConfig;
     * use yii\base\Event;
     *
     * Event::on(ProjectConfig::class, ProjectConfig::EVENT_AFTER_PARSE_CONFIG, function(ParseConfigEvent $e) {
     *      // Apply buffered changes
     * });
     * ```
     */
    const EVENT_AFTER_PARSE_CONFIG = 'afterParseConfig';

    // Properties
    // =========================================================================

    /**
     * @var array Current config as stored in database.
     */
    private $_storedConfig;

    /**
     * @var array A list of already parsed change paths
     */
    private $_parsedChanges = [];

    /**
     * @var array An array of paths to data structures used as intermediate storage.
     */
    private $_parsedConfigs = [];

    /**
     * @var array A list of all config files, defined by import directives in configuration files.
     */
    private $_configFileList = [];

    /**
     * @var array A list of Yaml files that have been modified during this request and need to be saved.
     */
    private $_modifiedYamlFiles = [];

    /**
     * @var array Config map currently used
     * @see _getStoredConfigMap()
     */
    private $_configMap;

    /**
     * @var bool Whether to update the config map on request end
     */
    private $_updateConfigMap = false;

    /**
     * @var bool Whether to update the config on request end
     */
    private $_updateConfig = false;

    /**
     * @var bool Whether we’re listening for the request end, to update the Yaml caches.
     * @see _updateLastParsedConfigCache()
     */
    private $_waitingToUpdateParsedConfigTimes = false;

    /**
     * @var bool Whether we're saving project configs to project.yaml
     * @see _useConfigFile()
     */
    private $_useConfigFile;

    /**
     * @var bool Whether the config's dateModified timestamp has been updated by this request.
     */
    private $_timestampUpdated = false;

    // Public methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        Craft::$app->on(Application::EVENT_AFTER_REQUEST, [$this, 'saveModifiedConfigData']);

        // If we're not using the project config file, load the stored config to emulate config files.
        // This is needed so we can make comparisons between the existing config and the modified config, as we're firing events.
        if (!$this->_useConfigFile()) {
            $this->_getConfigurationFromYaml();
        }

        parent::init();
    }

    /**
     * Returns a config value by its path.
     *
     * @param string $path
     * @param bool $getFromYaml whether data should be fetched `config/project.yaml` instead of the stored config. Defaults to `false`
     * @return mixed
     */
    public function get(string $path, $getFromYaml = false)
    {
        if ($getFromYaml) {
            $source = $this->_getConfigurationFromYaml();
        } else {
            $source = $this->_getStoredConfig();
        }

        return $this->_traverseDataArray($source, $path);
    }

    /**
     * Saves a value to the project config at a given path.
     *
     * @param string $path
     * @param mixed $value
     * @throws ErrorException
     * @throws Exception
     * @throws ServerErrorHttpException
     * @todo make sure $value is serializable and unserialable
     */
    public function save(string $path, $value)
    {
        $pathParts = explode('.', $path);

        $targetFilePath = null;

        if (!$this->_timestampUpdated) {
            $this->save('dateModified', DateTimeHelper::currentTimeStamp());
            $this->_timestampUpdated = true;
        }

        if ($this->_useConfigFile()) {
            $configMap = $this->_getStoredConfigMap();

            $topNode = array_shift($pathParts);
            $targetFilePath = $configMap[$topNode] ?? Craft::$app->getPath()->getConfigPath() . DIRECTORY_SEPARATOR . self::CONFIG_FILENAME;

            $config = $this->_parseYamlFile($targetFilePath);

            // For new top nodes, update the map
            if (empty($configMap[$topNode])) {
                $this->_mapNodeLocation($topNode, Craft::$app->getPath()->getConfigPath() . DIRECTORY_SEPARATOR . self::CONFIG_FILENAME);
                $this->_updateConfigMap = true;
            }
        } else {
            $config = $this->_getConfigurationFromYaml();
        }

        $this->_traverseDataArray($config, $path, $value, null === $value);

        $this->_saveConfig($config, $targetFilePath);

        // Ensure that new data is processed
        unset($this->_parsedChanges[$path]);

        return $this->processConfigChanges($path);
    }

    /**
     * Deletes a value from the configuration by its path.
     *
     * @param string $path
     */
    public function delete(string $path)
    {
        $this->save($path, null);
    }

    /**
     * Generates `config/project.yaml` based on the current stored config.
     */
    public function regenerateConfigFileFromStoredConfig()
    {
        $storedConfig = $this->_getStoredConfig();

        $basePath = Craft::$app->getPath()->getConfigPath();
        $baseFile = $basePath . '/' . self::CONFIG_FILENAME;

        $this->_saveConfig($storedConfig, $baseFile);
        $this->updateParsedConfigTimesAfterRequest();
    }

    /**
     * Applies all pending changes.
     */
    public function applyPendingChanges()
    {
        try {
            $changes = $this->_getPendingChanges();

            Craft::info('Looking for pending changes', __METHOD__);

            // If we're parsing all the changes, we better work the actual config map.
            $this->_configMap = $this->_generateConfigMap();

            if (!empty($changes['removedItems'])) {
                Craft::info('Parsing ' . count($changes['removedItems']) . ' removed configuration objects', __METHOD__);
                foreach ($changes['removedItems'] as $itemPath) {
                    $this->processConfigChanges($itemPath);
                }
            }

            if (!empty($changes['changedItems'])) {
                Craft::info('Parsing ' . count($changes['changedItems']) . ' changed configuration objects', __METHOD__);
                foreach ($changes['changedItems'] as $itemPath) {
                    $this->processConfigChanges($itemPath);
                }
            }

            if (!empty($changes['newItems'])) {
                Craft::info('Parsing ' . count($changes['newItems']) . ' new configuration objects', __METHOD__);
                foreach ($changes['newItems'] as $itemPath) {
                    $this->processConfigChanges($itemPath);
                }
            }

            Craft::info('Finalizing configuration parsing', __METHOD__);
            $this->trigger(self::EVENT_AFTER_PARSE_CONFIG, new ParseConfigEvent());

            $this->updateParsedConfigTimesAfterRequest();
            $this->_updateConfigMap = true;
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * Returns whether `config/project.yaml` has any pending changes that need to be applied to the project config.
     *
     * @return bool
     */
    public function isUpdatePending(): bool
    {
        // TODO remove after next breakpoint
        if (version_compare(Craft::$app->getInfo()->version, '3.1', '<')) {
            return false;
        }

        // If the file does not exist, but should, generate it
        if ($this->_useConfigFile() && !file_exists(Craft::$app->getPath()->getConfigPath() . '/' . self::CONFIG_FILENAME)) {
            $this->regenerateConfigFileFromStoredConfig();
            $this->saveModifiedConfigData();
        }

        if ($this->_useConfigFile() && $this->_areConfigFilesModified()) {
            $changes = $this->_getPendingChanges();

            foreach ($changes as $changeType) {
                if (!empty($changeType)) {
                    return true;
                }
            }

            $this->updateParsedConfigTimes();
        }

        return false;
    }

    /**
     * Processes config changes for a given path.
     *
     * @param string $configPath
     */
    public function processConfigChanges(string $configPath)
    {
        if (!empty($this->_parsedChanges[$configPath])) {
            return;
        }

        $this->_parsedChanges[$configPath] = true;

        $configData = $this->get($configPath, true);
        $storedConfigData = $this->get($configPath);

        $event = new ParseConfigEvent([
            'configPath' => $configPath,
            'newConfig' => $configData,
            'existingConfig' => $storedConfigData,
        ]);

        if ($storedConfigData && !$configData) {
            $this->trigger(self::EVENT_REMOVED_CONFIG_OBJECT, $event);
        } else {
            if (!$storedConfigData && $configData) {
                $this->trigger(self::EVENT_NEW_CONFIG_OBJECT, $event);
                // Might generate false positives, but is pretty fast.
            } else if ($configData !== null && $storedConfigData !== null && Json::encode($storedConfigData) !== Json::encode($configData)) {
                $this->trigger(self::EVENT_CHANGED_CONFIG_OBJECT, $event);
            } else {
                return;
            }
        }

        $this->_modifyStoredConfig($configPath, $event->newConfig);
        $this->updateParsedConfigTimesAfterRequest();
    }

    /**
     * Updates cached config file modified times after the request ends.
     */
    public function updateParsedConfigTimesAfterRequest()
    {
        if ($this->_waitingToUpdateParsedConfigTimes || !$this->_useConfigFile()) {
            return;
        }

        Craft::$app->on(Application::EVENT_AFTER_REQUEST, [$this, 'updateParsedConfigTimes']);
        $this->_waitingToUpdateParsedConfigTimes = true;
    }

    /**
     * Updates cached config file modified times immediately.
     *
     * @return bool
     */
    public function updateParsedConfigTimes(): bool
    {
        $fileList = $this->_getConfigFileModifiedTimes();
        return Craft::$app->getCache()->set(self::CACHE_KEY, $fileList, self::CACHE_DURATION);
    }

    /**
     * Saves all the config data that has been modified up to now.
     *
     * @throws ErrorException
     */
    public function saveModifiedConfigData()
    {
        $traverseAndClean = function(&$array) use (&$traverseAndClean) {
            $remove = [];
            foreach ($array as $key => &$value) {
                if (\is_array($value)) {
                    $traverseAndClean($value);
                    if (empty($value)) {
                        $remove[] = $key;
                    }
                }
            }

            // Remove empty stuff
            foreach ($remove as $removeKey) {
                unset($array[$removeKey]);
            }
        };

        if (!empty($this->_modifiedYamlFiles) && $this->_useConfigFile()) {
            // Save modified yaml files
            $fileList = array_keys($this->_modifiedYamlFiles);

            foreach ($fileList as $filePath) {
                $data = $this->_parsedConfigs[$filePath];
                $traverseAndClean($data);
                FileHelper::writeToFile($filePath, Yaml::dump($data, 20, 2));
            }
        }

        if (($this->_updateConfigMap && $this->_useConfigFile()) || $this->_updateConfig) {
            $info = Craft::$app->getInfo();

            if ($this->_updateConfigMap && $this->_useConfigFile()) {
                $info->configMap = Json::encode($this->_generateConfigMap());
            }

            if ($this->_updateConfig) {
                $info->config = serialize($this->_getConfigurationFromYaml());
            }

            Craft::$app->saveInfo($info);
        }
    }

    /**
     * Returns a summary of all pending config changes.
     *
     * @return array
     */
    public function getPendingChangeSummary(): array
    {
        $pendingChanges = $this->_getPendingChanges();

        $summary = [];

        // Reduce all the small changes to overall item changes.
        foreach ($pendingChanges as $type => $changes) {
            $summary[$type] = [];
            foreach ($changes as $path) {
                $pathParts = explode('.', $path);
                if (count($pathParts) > 1) {
                    $summary[$type][$pathParts[0] . '.' . $pathParts[1]] = true;
                }
            }
        }

        return $summary;
    }

    /**
     * Returns whether all schema versions stored in the config are compatible with the actual codebase.
     *
     * @return bool
     */
    public function getAreConfigSchemaVersionsCompatible(): bool
    {
        // TODO remove after next breakpoint
        if (version_compare(Craft::$app->getInfo()->version, '3.1', '<')) {
            return true;
        }

        $configSchemaVersion = (string) $this->get(self::CONFIG_SCHEMA_VERSION_KEY, true);

        if (version_compare((string) Craft::$app->schemaVersion, $configSchemaVersion, '<')) {
            return false;
        }

        $plugins = Craft::$app->getPlugins()->getAllPlugins();

        foreach ($plugins as $plugin) {
            /** @var Plugin $plugin */
            $configSchemaVersion = (string) $this->get(Plugins::CONFIG_PLUGINS_KEY.'.'.$plugin->handle.'.'.self::CONFIG_SCHEMA_VERSION_KEY, true);

            if (version_compare((string) $plugin->schemaVersion, $configSchemaVersion, '<')) {
                return false;
            }
        }

        return true;
    }

    // Private methods
    // =========================================================================

    /**
     * Retrieve a a config file tree with modified times based on the main configuration file.
     *
     * @return array
     */
    private function _getConfigFileModifiedTimes(): array
    {
        $fileList = $this->_getConfigFileList();

        $output = [];

        clearstatcache();
        foreach ($fileList as $file) {
            $output[$file] = FileHelper::lastModifiedTime($file);
        }

        return $output;
    }

    /**
     * Generate the configuration based on the configuration files.
     *
     * @return array
     */
    private function _getConfigurationFromYaml(): array
    {
        if ($this->_useConfigFile()) {
            $fileList = $this->_getConfigFileList();

            $generatedConfig = [];

            foreach ($fileList as $file) {
                $config = $this->_parseYamlFile($file);
                $generatedConfig = array_merge($generatedConfig, $config);
            }
        } else {
            if (empty($this->_parsedConfigs[self::CONFIG_KEY])) {
                $this->_parsedConfigs[self::CONFIG_KEY] = $this->_getStoredConfig();
            }

            $generatedConfig = $this->_parsedConfigs[self::CONFIG_KEY];
        }

        return $generatedConfig;
    }

    /**
     * Return parsed YAML contents of a file, holding the data in cache.
     *
     * @param string $file
     * @return mixed
     */
    private function _parseYamlFile(string $file)
    {
        if (empty($this->_parsedConfigs[$file])) {
            $this->_parsedConfigs[$file] = file_exists($file) ? Yaml::parseFile($file) : [];
        }

        return $this->_parsedConfigs[$file];
    }

    /**
     * Map a new node to a yaml file.
     *
     * @param $node
     * @param $location
     * @throws ServerErrorHttpException
     */
    private function _mapNodeLocation($node, $location)
    {
        $this->_getStoredConfigMap();
        $this->_configMap[$node] = $location;
    }

    /**
     * Modify the stored config with new data.
     *
     * @param $configPath
     * @param $data
     */
    private function _modifyStoredConfig($configPath, $data)
    {
        $this->_traverseDataArray($this->_storedConfig, $configPath, $data);
        $this->_updateConfig = true;
    }

    /**
     * Get the stored config map.
     *
     * @return array
     * @throws ServerErrorHttpException
     */
    private function _getStoredConfigMap(): array
    {
        if ($this->_configMap !== null) {
            return $this->_configMap;
        }

        return $this->_configMap = Json::decode(Craft::$app->getInfo()->configMap) ?? [];
    }

    /**
     * Get the stored config.
     *
     * @return array
     */
    private function _getStoredConfig(): array
    {
        if (empty($this->_storedConfig)) {
            $configData = Craft::$app->getInfo()->config;
            $this->_storedConfig = $configData ? unserialize($configData, ['allowed_classes' => false]) : [];
        }

        return $this->_storedConfig;
    }

    /**
     * Return a nested array for pending config changes
     *
     * @return array
     */
    private function _getPendingChanges(): array
    {
        $changes = [
            'newItems' => [],
            'removedItems' => [],
            'changedItems' => [],
        ];

        $configData = $this->_getConfigurationFromYaml();
        $currentConfig = $this->_getStoredConfig();

        $flatConfig = [];
        $flatCurrent = [];

        unset($configData['dateModified'], $currentConfig['dateModified'], $configData['imports'], $currentConfig['imports']);

        // flatten both configs so we can compare them.

        $flatten = function($array, $path, &$result) use (&$flatten) {
            foreach ($array as $key => $value) {
                $thisPath = ltrim($path . '.' . $key, '.');

                if (is_array($value)) {
                    $flatten($value, $thisPath, $result);
                } else {
                    $result[$thisPath] = $value;
                }
            }
        };

        $flatten($configData, '', $flatConfig);
        $flatten($currentConfig, '', $flatCurrent);

        // Compare and if something is different, mark the immediate parent as changed.
        foreach ($flatConfig as $key => $value) {
            // Drop the last part of path
            $immediateParent = pathinfo($key, PATHINFO_FILENAME);

            if (!array_key_exists($key, $flatCurrent)) {
                $changes['newItems'][] = $immediateParent;
            } elseif ($flatCurrent[$key] !== $value) {
                $changes['changedItems'][] = $immediateParent;
            }

            unset($flatCurrent[$key]);
        }

        $changes['removedItems'] = array_keys($flatCurrent);

        foreach ($changes['removedItems'] as &$removedItem) {
            // Drop the last part of path
            $removedItem = pathinfo($removedItem, PATHINFO_FILENAME);
        }

        // Sort by number of dots to ensure deepest paths listed first
        $sorter = function($a, $b) {
            $aDepth = substr_count($a, '.');
            $bDepth = substr_count($b, '.');

            if ($aDepth === $bDepth) {
                return 0;
            }

            return $aDepth > $bDepth ? -1 : 1;
        };

        $changes['newItems'] = array_unique($changes['newItems']);
        $changes['removedItems'] = array_unique($changes['removedItems']);
        $changes['changedItems'] = array_unique($changes['changedItems']);

        uasort($changes['newItems'], $sorter);
        uasort($changes['removedItems'], $sorter);
        uasort($changes['changedItems'], $sorter);

        return $changes;
    }

    /**
     * Generate the configuration mapping data from configuration files.
     *
     * @return array
     */
    private function _generateConfigMap(): array
    {
        $fileList = $this->_getConfigFileList();
        $nodes = [];

        foreach ($fileList as $file) {
            $config = $this->_parseYamlFile($file);

            // Take record of top nodes
            $topNodes = array_keys($config);
            foreach ($topNodes as $topNode) {
                $nodes[$topNode] = $file;
            }
        }

        unset($nodes['imports']);
        return $nodes;
    }

    /**
     * Return true if any of the config files have been modified since last we checked.
     *
     * @return bool
     */
    private function _areConfigFilesModified(): bool
    {
        $cachedModifiedTimes = Craft::$app->getCache()->get(self::CACHE_KEY);

        if (!is_array($cachedModifiedTimes) || empty($cachedModifiedTimes)) {
            return true;
        }

        foreach ($cachedModifiedTimes as $file => $modified) {
            if (!file_exists($file) || FileHelper::lastModifiedTime($file) > $modified) {
                return true;
            }
        }

        // Re-cache
        Craft::$app->getCache()->set(self::CACHE_KEY, $cachedModifiedTimes, self::CACHE_DURATION);

        return false;
    }

    /**
     * Load the config file and figure out all the files imported and used.
     *
     * @return array
     */
    private function _getConfigFileList(): array
    {
        if (!empty($this->_configFileList)) {
            return $this->_configFileList;
        }

        $basePath = Craft::$app->getPath()->getConfigPath();
        $baseFile = $basePath . DIRECTORY_SEPARATOR . self::CONFIG_FILENAME;

        $traverseFile = function($filePath) use (&$traverseFile) {
            $fileList = [$filePath];
            $config = $this->_parseYamlFile($filePath);
            $fileDir = pathinfo($filePath, PATHINFO_DIRNAME);

            if (isset($config['imports'])) {
                foreach ($config['imports'] as $file) {
                    if (PathHelper::ensurePathIsContained($file)) {
                        $fileList = array_merge($fileList, $traverseFile($fileDir . DIRECTORY_SEPARATOR . $file));
                    }
                }
            }

            return $fileList;
        };

        return $this->_configFileList = $traverseFile($baseFile);
    }

    /**
     * Save configuration data to a path.
     *
     * @param array $data
     * @param string|null $configPath
     * @throws ErrorException
     */
    private function _saveConfig(array $data, string $configPath = null)
    {
        if ($this->_useConfigFile() && $configPath) {
            $this->_parsedConfigs[$configPath] = $data;
            $this->_modifiedYamlFiles[$configPath] = true;
        } else {
            $this->_parsedConfigs[self::CONFIG_KEY] = $data;
        }
    }

    /**
     * Whether to use the config file or not.
     *
     * @return bool
     */
    private function _useConfigFile(): bool
    {
        if ($this->_useConfigFile !== null) {
            return $this->_useConfigFile;
        }

        return $this->_useConfigFile = Craft::$app->getConfig()->getGeneral()->useProjectConfigFile;
    }

    /**
     * Traverse a nested data array according to path and perform an action depending on parameters.
     *
     * @param array $data A nested array of data to traverse
     * @param array|string $path Path used to traverse the array. Either an array or a dot.based.path
     * @param null $value Value to set at the destination. If left null, will return the value, unless deleting
     * @param bool $delete Whether to delete the value at the destination or not.
     * @return mixed|null
     */
    private function _traverseDataArray(array &$data, $path, $value = null, $delete = false)
    {
        if (is_string($path)) {
            $path = explode('.', $path);
        }

        $nextSegment = array_shift($path);

        // Last piece?
        if (count($path) === 0) {
            if ($delete) {
                unset($data[$nextSegment]);
            } else if (null === $value) {
                return $data[$nextSegment] ?? null;
            } else {
                $data[$nextSegment] = $value;
            }
        } else {
            if (!isset($data[$nextSegment])) {
                // If the path doesn't exist, it's fine if we wanted to delete or read
                if ($delete || null === $value) {
                    return;
                }

                $data[$nextSegment] = [];
            }

            return $this->_traverseDataArray($data[$nextSegment], $path, $value, $delete);
        }
    }
}
