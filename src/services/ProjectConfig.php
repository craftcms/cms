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

    // Regexp patterns
    // -------------------------------------------------------------------------
    const UID_PATTERN = '[0-f]{8}-[0-f]{4}-[0-f]{4}-[0-f]{4}-[0-f]{12}';

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
     * @var array Current configuration as stored in YAML
     */
    private $_config;

    /**
     * @var array Current config map
     */
    private $_configMap;

    // Public methods
    // =========================================================================

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
            $source = $this->_getCurrentConfig();
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
     * @param $value
     * @return bool
     */
    public function save(string $path, $value)
    {
        $pathParts = explode('.', $path);
        $endPart = end($pathParts);

        $configMap = $this->_getCurrentConfigMap();
        $nodeConfig = $configMap['nodes'] ?? [];
        $map = $configMap['map'] ?? [];

        $existingNodePath = null;
        // Does it look like UID?
        if (preg_match('/'.self::UID_PATTERN.'/i', $endPart) && !empty($map[$endPart])) {
            $existingNodePath = $map[$endPart];
        }

        $topNode = array_shift($pathParts);
        $targetFilePath = $nodeConfig[$topNode] ?? Craft::$app->getPath()->getConfigPath().'/system.yml';
        $nodePath = $targetFilePath.'/'.$path;

        // Moving data between locations
        $previousFilePath = null;

        // Delete previous stored data?
        if ($existingNodePath && ($existingNodePath !== $nodePath || null === $value)) {
            $parts = explode('/', $existingNodePath);
            $previousNodeLocation = array_pop($parts);
            $previousFilePath = implode('/', $parts);
            $previousYaml = Yaml::parseFile($previousFilePath);
            $arrayAccess = $this->_nodePathToArrayAccess($previousNodeLocation);
            eval('unset($previousYaml'.$arrayAccess.');');
        }

        // If this is a moving node within the same file.
        if  ($targetFilePath == $previousFilePath || null === $value) {
            $targetYaml = $previousYaml;
        } else {
            // If this was a moving file from a different file.
            if ($previousFilePath) {
                $this->_saveYaml($previousYaml, $previousFilePath);
            }
            $targetYaml = file_exists($targetFilePath) ? Yaml::parseFile($targetFilePath) : [];
        }

        if (null !== $value) {
            $arrayAccess = $this->_nodePathToArrayAccess($path);
            eval('$targetYaml'.$arrayAccess.' = $value;');
        }

        $this->_saveYaml($targetYaml, $targetFilePath);
        $this->updateConfigMap();

        return true;
    }

    /**
     * Process config changes for a path.
     *
     * @param $configPath
     * @throws \yii\web\ServerErrorHttpException
     */
    public function processConfigChanges($configPath): bool {
        $configData = $this->get($configPath, true);
        $snapshotData = $this->get($configPath);

        if ($snapshotData && !$configData) {
            $this->trigger(self::EVENT_REMOVED_CONFIG_OBJECT, new ParseConfigEvent([
                'configPath' => $configPath
            ]));
        } else {
            if (!$snapshotData) {
                $this->trigger(self::EVENT_NEW_CONFIG_OBJECT, new ParseConfigEvent([
                    'configPath' => $configPath
                ]));
            } else if (Json::encode($snapshotData) !== Json::encode($configData)) {
                $this->trigger(self::EVENT_CHANGED_CONFIG_OBJECT, new ParseConfigEvent([
                    'configPath' => $configPath
                ]));
            } else {
                return true;
            }
        }

        $snapshot = $this->_getCurrentSnapshot();
        $arrayAccess = $this->_nodePathToArrayAccess($configPath);
        eval('$snapshot'.$arrayAccess.' = $configData;');

        return $this->_saveSnapshot($snapshot) && $this->_updateLastParsedConfigCache();
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
        $this->_updateLastParsedConfigCache();
    }

    /**
     * Apply any pending changes
     */
    public function applyPendingChanges()
    {
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $changes = $this->_getPendingChanges();

            Craft::info('Looking for pending changes', __METHOD__);

            print_r($changes);
            die();
            if (!empty($changes['newItems'])) {
                Craft::info('Parsing '.count($changes['newItems']).' new configuration objects', __METHOD__);
                foreach ($changes['newItems'] as $itemPath) {
                    $this->trigger(self::EVENT_NEW_CONFIG_OBJECT, new ParseConfigEvent([
                        'configPath' => $itemPath
                    ]));
                }
            }

            if (!empty($changes['changedItems'])) {
                Craft::info('Parsing '.count($changes['changedItems']).' changed configuration objects', __METHOD__);
                foreach ($changes['changedItems'] as $itemPath) {
                    $this->trigger(self::EVENT_CHANGED_CONFIG_OBJECT, new ParseConfigEvent([
                        'configPath' => $itemPath
                    ]));
                }
            }

            if (!empty($changes['removedItems'])) {
                Craft::info('Parsing '.count($changes['removedItems']).' removed configuration objects', __METHOD__);

                foreach ($changes['removedItems'] as $itemPath) {
                    $this->trigger(self::EVENT_REMOVED_CONFIG_OBJECT, new ParseConfigEvent([
                        'configPath' => $itemPath
                    ]));
                }
            }

            Craft::info('Finalizing configuration parsing', __METHOD__);
            $this->trigger(self::EVENT_AFTER_PARSE_CONFIG, new ParseConfigEvent());

            // TODO finish this
            throw new Exception('Stuff');
            $transaction->commit();
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
     * Returns true if config mapping might have changed due to changes in file config tree or modify times.
     *
     * @return bool
     */
    public function isConfigMapOutdated(): bool
    {
        $yamlTree = $this->_getConfigFileModifiedTimes();
        $cachedTree = $this->_getConfigFileModifyDates();

        // Tree has changed
        if (\count(array_diff_key($yamlTree, $cachedTree)) || \count(array_diff_key($cachedTree, $yamlTree))) {
            return true;
        }

        // Date modified has changed
        foreach ($yamlTree as $file => $dateModified) {
            if ($dateModified !== $cachedTree[$file]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update the configuration mapping.
     *
     * @return bool
     * @throws \yii\web\ServerErrorHttpException
     */
    public function updateConfigMap(): bool
    {
        $configMap = $this->_generateConfigMap();
        $this->_configMap = $configMap;

        $info = Craft::$app->getInfo();
        $info->configMap = Json::encode($configMap);
        Craft::$app->saveInfo($info);

        $this->_updateLastParsedConfigCache();

        return true;
    }

    /**
     * Update the configuration snapshot.
     *
     * @return bool
     * @throws \yii\web\ServerErrorHttpException
     */
    public function regenerateSnapshotFromConfig(): bool
    {
        $snapshot = $this->_getCofigurationFromConfigFiles();
        $this->_snapshot = $snapshot;

        $info = Craft::$app->getInfo();
        $info->configSnapshot = serialize($snapshot);
        Craft::$app->saveInfo($info);

        $this->_updateLastParsedConfigCache();

        return true;
    }

    // Private methods
    // =========================================================================

    /**
     * Get config file modified dates.
     *
     * @return array
     */
    private function _getConfigFileModifyDates(): array
    {
        $cachedTimes = Craft::$app->getCache()->get(self::CACHE_KEY);

        if (!$cachedTimes) {
            return [];
        }

        $this->_updateLastParsedConfigCache($cachedTimes);

        return $cachedTimes;
    }

    /**
     * Update config file modified date cache. If no modified dates passed, the config file tree will be parsed
     * to figure out the modified dates.
     *
     * @param array|null $fileList
     * @return bool
     */
    private function _updateLastParsedConfigCache(array $fileList = null): bool
    {
        if (!$fileList) {
            $fileList = $this->_getConfigFileModifiedTimes();
        }

        return Craft::$app->getCache()->set(self::CACHE_KEY, $fileList, self::CACHE_DURATION);
    }

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
    private function _getCofigurationFromConfigFiles(): array
    {
        $fileList = $this->_getConfigFileList();

        $snapshot = [];

        foreach ($fileList as $file) {
            $config = Yaml::parseFile($file);
            $snapshot = array_merge($snapshot, $config);
        }

        return $snapshot;
    }

    /**
     * Get the stored config map.
     *
     * @return array
     * @throws \yii\web\ServerErrorHttpException
     */
    private function _getCurrentConfigMap(): array
    {
        return Json::decode(Craft::$app->getInfo()->configMap) ?? [];
    }

    /**
     * Get the stored snapshot.
     *
     * @return array
     * @throws \yii\web\ServerErrorHttpException
     */
    private function _getCurrentSnapshot(): array
    {
        if (empty($this->_snapshot)) {
            $this->_snapshot = unserialize(Craft::$app->getInfo()->configSnapshot, ['allowed_classes' => false]);
        }

        return $this->_snapshot;
    }

    /**
     * Get the current config.
     *
     * @return array
     */
    private function _getCurrentConfig(): array
    {
        if (empty($this->_config)) {
            $this->_config = $this->_getCofigurationFromConfigFiles();
        }

        return $this->_config;
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

        $configSnapshot = $this->_getCofigurationFromConfigFiles();
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

        return ProjectConfigHelper::generateConfigMap($fileList);

    }

    /**
     * Load the system.yml file and figure out all the files imported and used.
     *
     * @return array
     */
    private function _getConfigFileList(): array
    {
        $basePath = Craft::$app->getPath()->getConfigPath();
        $baseFile = $basePath.'/system.yml';

        $traverseFile = function($filePath) use (&$traverseFile) {
            $fileList = [$filePath];
            $config = Yaml::parseFile($filePath);
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


        return $traverseFile($baseFile);
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

        $traverseAndClean($data);

        FileHelper::writeToFile($path, Yaml::dump($data, 10, 2));
    }
}
