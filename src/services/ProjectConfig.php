<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\db\Query;
use craft\events\ParseConfigEvent;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\Path as PathHelper;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use Symfony\Component\Yaml\Yaml;
use yii\base\Application;
use yii\base\Component;
use yii\base\Exception;

/**
 * Project config service.
 * An instance of the ProjectConfig service is globally accessible in Craft via [[\craft\base\ApplicationTrait::ProjectConfig()|<code>Craft::$app->projectConfig</code>]].
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
    const CACHE_DURATION = 60 * 60 * 24 * 30;

    // Config entities
    // -------------------------------------------------------------------------
    const ENTITY_SITES = 'sites';
    const ENTITY_SECTIONS = 'sections';
    const ENTITY_FIELDS = 'fields';
    const ENTITY_VOLUMES = 'volumes';

    // TODO move this to UID validator class
    // TODO update StringHelper::isUUID() to use that
    // Regexp patterns
    // -------------------------------------------------------------------------
    const UID_PATTERN = '[a-zA-Z0-9_-]+';

    // Events
    // =========================================================================
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

    /**
     * @var array Current snapshot as stored in database.
     */
    private $_snapshot;

    /**
     * @var array A list of already parsed change paths
     */
    private $_parsedChanges = [];

    /**
     * @var array An array of paths to data structures used as intermediate storage.
     */
    private $_parsedConfigs = [];

    /**
     * @var array A list of all config files, defined by import directives in system.yml.
     */
    private $_configFileList = [];

    /**
     * @var array A list of Yaml files that have been modified during this request and need to be saved.
     */
    private $_modifiedYamlFiles = [];

    /**
     * @var array Config map currently used
     */
    private $_configMap = [];

    /**
     * @var bool Whether to update the config map on request end
     */
    private $_updateConfigMap = false;

    /**
     * @var bool Whether to update the snapshot on request end
     */
    private $_updateSnapshot = false;

    /**
     * @var bool Whether we’re listening for the request end, to update the YML caches.
     * @see _updateLastParsedConfigCache()
     */
    private $_waitingToUpdateParsedConfigTimes = false;

    // Public methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init() {
        Craft::$app->on(Application::EVENT_AFTER_REQUEST, [$this, 'saveModifiedConfigData']);
        parent::init();
    }

    /**
     * Get a value by path from the snapshot.
     *
     * @param string $path
     * @param bool $getFromConfig whether data should be fetched from config instead of snapshot. Defaults to `false`
     * @return array|mixed|null
     */
    public function get(string $path, $getFromConfig = false)
    {
        if ($getFromConfig) {
            $source = $this->_getConfigurationFromConfigFiles();
        } else {
            $source = $this->_getCurrentSnapshot();
        }

        $arrayAccess = $this->_nodePathToArrayAccess($path);

        // TODO figure out a better but not convoluted way without eval
        return eval('return isset($source'.$arrayAccess.') ? $source'.$arrayAccess.' : null;');
    }

    /**
     * Save a value to YML configuration by path.
     *
     * @param string $path
     * @param mixed $value
     * @param bool $updateSilently whether updates should be broadcast via updates. Defaults to true.
     * @return void
     */
    public function save(string $path, $value, bool $updateSilently = false)
    {
        $pathParts = explode('.', $path);

        $configMap = $this->_getStoredConfigMap();

        $topNode = array_shift($pathParts);
        $targetFilePath = $configMap[$topNode] ?? Craft::$app->getPath()->getConfigPath().'/system.yml';

        $targetYaml = $this->_parseYamlFile($targetFilePath);

        $arrayAccess = $this->_nodePathToArrayAccess($path);

        if (null === $value) {
            eval('unset($targetYaml'.$arrayAccess.');');
        } else {
            eval('$targetYaml'.$arrayAccess.' = $value;');
        }

        $this->_saveYaml($targetYaml, $targetFilePath);

        // For new top nodes, update the map
        if (empty($configMap[$topNode])) {
            $this->_mapNodeLocation($topNode, Craft::$app->getPath()->getConfigPath().'/system.yml');
            $this->_updateConfigMap = true;
        }

        // Ensure that new data is processed
        unset($this->_parsedChanges[$path]);

        if ($updateSilently) {
            return true;
        }

        return $this->processConfigChanges($path);
    }

    /**
     * Delete a value from the YML configuration by its path.
     *
     * @param string $path
     * @param bool $deleteSilently whether delete should be broadcast via updates. Defaults to true.
     */
    public function delete($path, bool $deleteSilently = false) {
        $this->save($path, null, $deleteSilently);
    }

    /**
     * Generate the configuration file based on the current snapshot.
     *
     * @return void
     */
    public function regenerateConfigFileFromSnapshot()
    {
        $snapshot = $this->_getCurrentSnapshot();

        $basePath = Craft::$app->getPath()->getConfigPath();
        $baseFile = $basePath.'/system.yml';

        $this->_saveYaml($snapshot, $baseFile);
        $this->updateParsedConfigTimesAfterRequest();
    }

    /**
     * Apply all pending changes
     */
    public function applyPendingChanges()
    {
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $changes = $this->_getPendingChanges();

            Craft::info('Looking for pending changes', __METHOD__);

            // If we're parsing all the changes, we better work the actual config map.
            $this->_configMap = $this->_generateConfigMap();

            if (!empty($changes['removedItems'])) {
                Craft::info('Parsing '.count($changes['removedItems']).' removed configuration objects', __METHOD__);
                foreach ($changes['removedItems'] as $itemPath) {
                    $this->processConfigChanges($itemPath);
                }
            }

            if (!empty($changes['changedItems'])) {
                Craft::info('Parsing '.count($changes['changedItems']).' changed configuration objects', __METHOD__);
                foreach ($changes['changedItems'] as $itemPath) {
                    $this->processConfigChanges($itemPath);
                }
            }

            if (!empty($changes['newItems'])) {
                Craft::info('Parsing '.count($changes['newItems']).' new configuration objects', __METHOD__);
                foreach ($changes['newItems'] as $itemPath) {
                    $this->processConfigChanges($itemPath);
                }
            }

            Craft::info('Finalizing configuration parsing', __METHOD__);
            $this->trigger(self::EVENT_AFTER_PARSE_CONFIG, new ParseConfigEvent());

            $transaction->commit();

            $this->updateParsedConfigTimesAfterRequest();
            $this->_updateConfigMap = true;
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

    }

    /**
     * Whether there is an update pending based on config and snapshot.
     *
     * @return bool
     */
    public function isUpdatePending(): bool
    {
        $changes = $this->_getPendingChanges();

        foreach ($changes as $changeType) {
            if (!empty($changeType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Regenerate the configuration snapshot.
     *
     * @return bool
     * @throws \yii\web\ServerErrorHttpException
     */
    public function regenerateSnapshotFromConfig(): bool
    {
        $this->_updateSnapshot = true;
        $this->_updateConfigMap = true;

        return true;
    }

    /**
     * Process config changes for a path.
     *
     * @param $configPath
     */
    public function processConfigChanges($configPath)
    {
        if (!empty($this->_parsedChanges[$configPath])) {
            return;
        }

        $this->_parsedChanges[$configPath] = true;

        $configData = $this->get($configPath, true);
        $snapshotData = $this->get($configPath);

        $event = new ParseConfigEvent([
            'configPath' => $configPath,
            'configData' => $configData,
            'snapshotData' => $snapshotData,
        ]);

        if ($snapshotData && !$configData) {
            $this->trigger(self::EVENT_REMOVED_CONFIG_OBJECT, $event);
        } else {
            if (!$snapshotData && $configData) {
                $this->trigger(self::EVENT_NEW_CONFIG_OBJECT, $event);
                // Might generate false positives, but is pretty fast.
            } else if (null !== $configData && null !== $snapshotData && Json::encode($snapshotData) !== Json::encode($configData)) {
                $this->trigger(self::EVENT_CHANGED_CONFIG_OBJECT, $event);
            } else {
                return;
            }
        }

        $this->_modifySnapshot($configPath, $event->configData);
        $this->updateParsedConfigTimesAfterRequest();
    }

    /**
     * Update cached config file modified times after the request ends.
     *
     * @return void
     */
    public function updateParsedConfigTimesAfterRequest()
    {
        if ($this->_waitingToUpdateParsedConfigTimes) {
            return;
        }

        Craft::$app->on(Application::EVENT_AFTER_REQUEST, [$this, 'updateParsedConfigTimes']);
        $this->_waitingToUpdateParsedConfigTimes = true;
    }

    /**
     * Update cached config file modified times immediately.
     *
     * @return bool
     */
    public function updateParsedConfigTimes(): bool
    {
        $fileList = $this->_getConfigFileModifiedTimes();
        return Craft::$app->getCache()->set(self::CACHE_KEY, $fileList, self::CACHE_DURATION);
    }

    /**
     * Save all the Yaml files that have been modified up to now.
     *
     * @throws \yii\base\ErrorException
     */
    public function saveModifiedConfigData() {
        $traverseAndClean = function (&$array) use (&$traverseAndClean) {
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

        if (!empty($this->_modifiedYamlFiles)) {
            // Save modified yaml files
            $fileList = array_keys($this->_modifiedYamlFiles);

            foreach ($fileList as $filePath) {
                $data = $this->_parsedConfigs[$filePath];
                $traverseAndClean($data);
                FileHelper::writeToFile($filePath, Yaml::dump($data, 20, 2));
            }
        }

        if ($this->_updateConfigMap || $this->_updateSnapshot) {
            $info = Craft::$app->getInfo();

            if ($this->_updateConfigMap) {
                $info->configMap = Json::encode($this->_generateConfigMap());
            }

            if ($this->_updateSnapshot) {
                $info->configSnapshot = serialize($this->_getConfigurationFromConfigFiles());
            }

            Craft::$app->saveInfo($info);
        }

    }

    // Private methods
    // =========================================================================

    /**
     * Retrieve a a config file tree with modified times based on the main `system.yml` configuration file.
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
     * Generate the configuration snapshot based on the configuration files.
     *
     * @return array
     */
    private function _getConfigurationFromConfigFiles(): array
    {
        $fileList = $this->_getConfigFileList();

        $snapshot = [];

        foreach ($fileList as $file) {
            $config = $this->_parseYamlFile($file);
            $snapshot = array_merge($snapshot, $config);
        }

        return $snapshot;
    }

    /**
     * Return parsed YAML contents of a file, holding the data in cache.
     *
     * @param string $file
     * @return mixed
     */
    private function _parseYamlFile(string $file) {
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
     * @throws \yii\web\ServerErrorHttpException
     */
    private function _mapNodeLocation($node, $location)
    {
        $this->_getStoredConfigMap();
        $this->_configMap[$node] = $location;

    }

    /**
     * Modify the existing snapshot with new data.
     *
     * @param $configPath
     * @param $data
     */
    private function _modifySnapshot($configPath, $data)
    {
        $arrayAccess = $this->_nodePathToArrayAccess($configPath);
        eval('$this->_snapshot'.$arrayAccess.' = $data;');
        $this->_updateSnapshot = true;
    }
    /**
     * Get the stored config map.
     *
     * @return array
     * @throws \yii\web\ServerErrorHttpException
     */
    private function _getStoredConfigMap(): array
    {
        if (empty($this->_configMap)) {
            $this->_configMap = Json::decode(Craft::$app->getInfo()->configMap) ?? [];
        }

        return $this->_configMap;
    }

    /**
     * Get the stored snapshot.
     *
     * @return array
     */
    private function _getCurrentSnapshot(): array
    {
        if (empty($this->_snapshot)) {
            $snapshotData = Craft::$app->getInfo()->configSnapshot;
            $this->_snapshot = $snapshotData ? unserialize($snapshotData, ['allowed_classes' => false]) : [];
        }

        return $this->_snapshot;
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

        $configSnapshot = $this->_getConfigurationFromConfigFiles();
        $currentSnapshot = $this->_getCurrentSnapshot();

        $flatConfig = [];
        $flatCurrent = [];

        unset($configSnapshot['imports'], $currentSnapshot['imports']);

        // flatten both snapshots so we can compare them.

        $flatten = function ($array, $path, &$result) use (&$flatten) {
            foreach ($array as $key => $value) {
                $thisPath = ltrim($path.'.'.$key, '.');

                if (is_array($value)) {
                    $flatten($value, $thisPath, $result);
                } else {
                    $result[$thisPath] = $value;
                }
            }
        };

        $flatten($configSnapshot, '', $flatConfig);
        $flatten($currentSnapshot, '', $flatCurrent);

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
     * Load the system.yml file and figure out all the files imported and used.
     *
     * @return array
     */
    private function _getConfigFileList(): array
    {
        if (!empty($this->_configFileList)) {
            return $this->_configFileList;
        }

        $basePath = Craft::$app->getPath()->getConfigPath();
        $baseFile = $basePath.'/system.yml';

        $traverseFile = function($filePath) use (&$traverseFile) {
            $fileList = [$filePath];
            $config = $this->_parseYamlFile($filePath);
            $fileDir = pathinfo($filePath, PATHINFO_DIRNAME);

            if (isset($config['imports'])) {
                foreach ($config['imports'] as $file) {
                    if (PathHelper::ensurePathIsContained($file)) {
                        $fileList = array_merge($fileList, $traverseFile($fileDir.'/'.$file));
                    }
                }
            }

            return $fileList;
        };


        return $this->_configFileList = $traverseFile($baseFile);
    }

    /**
     * Convert a node string to a string to be used in `eval()` to access an array key.
     *
     * @param string $nodePath
     * @return string
     */
    private function _nodePathToArrayAccess(string $nodePath): string
    {
        // Clean up!
        $nodePath = preg_replace('/[^a-z0-9\-\.]/i', '', $nodePath);
        return "['".preg_replace('/\./', "']['", $nodePath)."']";
    }

    /**
     * Save YML data to a file, cleaning up empty values while doing so.
     *
     * @param array $data
     * @param string $path
     * @throws \yii\base\ErrorException
     */
    private function _saveYaml(array $data, string $path)
    {
        $this->_parsedConfigs[$path] = $data;
        $this->_modifiedYamlFiles[$path] = true;
    }
}
