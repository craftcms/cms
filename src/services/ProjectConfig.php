<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\elements\User;
use craft\errors\OperationAbortedException;
use craft\events\ConfigEvent;
use craft\events\RebuildConfigEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\models\GqlToken;
use Symfony\Component\Yaml\Yaml;
use yii\base\Application;
use yii\base\Component;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\NotSupportedException;
use yii\caching\DbQueryDependency;
use yii\web\ServerErrorHttpException;

/**
 * Project config service.
 * An instance of the ProjectConfig service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getProjectConfig()|`Craft::$app->projectConfig`]].
 *
 * @property-read bool $isApplyingYamlChanges
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.0
 */
class ProjectConfig extends Component
{
    // Cache settings
    // -------------------------------------------------------------------------

    /**
     * The cache key that is used to store the modified time of the project config files, at the time they were last applied.
     */
    const CACHE_KEY = 'projectConfig:files';
    /**
     * The cache key that is used to store the loaded project config data.
     */
    const STORED_CACHE_KEY = 'projectConfig:internal';
    /**
     * The duration that project config caches should be cached.
     */
    const CACHE_DURATION = 2592000; // 30 days
    /**
     * @deprecated in 3.5.0
     */
    const CONFIG_KEY = 'storedConfig';
    /**
     * @var string Filename for base config file
     * @since 3.1.0
     */
    const CONFIG_FILENAME = 'project.yaml';
    /**
     * Filename for base config delta files
     *
     * @since 3.4.0
     */
    const CONFIG_DELTA_FILENAME = 'delta.yaml';
    /**
     * The project config key that the Craft schema version is stored at.
     */
    const CONFIG_SCHEMA_VERSION_KEY = 'system.schemaVersion';
    /**
     * The array key to use for signaling ordered-to-associative array conversion.
     *
     * @since 3.4.0
     */
    const CONFIG_ASSOC_KEY = '__assoc__';
    /**
     * @since 3.4.0
     * @deprecated in 3.5.0
     */
    const CONFIG_ALL_KEY = '__all__';

    // Regexp patterns
    // -------------------------------------------------------------------------

    /**
     * Regexp pattern to determine a string that could be used as an UID.
     */
    const UID_PATTERN = '[a-zA-Z0-9_-]+';

    // Events
    // -------------------------------------------------------------------------

    /**
     * @event ConfigEvent The event that is triggered when an item is added to the config.
     *
     * ---
     *
     * ```php
     * use craft\events\ParseConfigEvent;
     * use craft\services\ProjectConfig;
     * use yii\base\Event;
     *
     * Event::on(ProjectConfig::class, ProjectConfig::EVENT_ADD_ITEM, function(ParseConfigEvent $e) {
     *     // Ensure the item is also added in the database...
     * });
     * ```
     */
    const EVENT_ADD_ITEM = 'addItem';

    /**
     * @event ConfigEvent The event that is triggered when an item is updated in the config.
     *
     * ---
     *
     * ```php
     * use craft\events\ParseConfigEvent;
     * use craft\services\ProjectConfig;
     * use yii\base\Event;
     *
     * Event::on(ProjectConfig::class, ProjectConfig::EVENT_UPDATE_ITEM, function(ParseConfigEvent $e) {
     *     // Ensure the item is also updated in the database...
     * });
     * ```
     */
    const EVENT_UPDATE_ITEM = 'updateItem';

    /**
     * @event ConfigEvent The event that is triggered when an item is removed from the config.
     *
     * ---
     *
     * ```php
     * use craft\events\ParseConfigEvent;
     * use craft\services\ProjectConfig;
     * use yii\base\Event;
     *
     * Event::on(ProjectConfig::class, ProjectConfig::EVENT_REMOVE_ITEM, function(ParseConfigEvent $e) {
     *     // Ensure the item is also removed in the database...
     * });
     * ```
     */
    const EVENT_REMOVE_ITEM = 'removeItem';

    /**
     * @event Event The event that is triggered after pending project config file changes have been applied.
     */
    const EVENT_AFTER_APPLY_CHANGES = 'afterApplyChanges';

    /**
     * @event RebuildConfigEvent The event that is triggered when the project config is being rebuilt.
     *
     * ---
     *
     * ```php
     * use craft\events\RebuildConfigEvent;
     * use craft\services\ProjectConfig;
     * use yii\base\Event;
     *
     * Event::on(ProjectConfig::class, ProjectConfig::EVENT_REBUILD, function(RebuildConfigEvent $e) {
     *     // Add plugin's project config data...
     *    $e->config['myPlugin']['key'] = $value;
     * });
     * ```
     *
     * @since 3.1.20
     */
    const EVENT_REBUILD = 'rebuild';

    /**
     * @var string The folder name to save the project config files in, within the `config/` folder.
     * @since 3.5.0
     */
    public $folderName = 'project';

    /**
     * @var int The maximum number of project.yaml deltas to store in storage/config-backups/
     * @since 3.4.0
     */
    public $maxDeltas = 50;

    /**
     * @var int The maximum number of times deferred events can be re-deferred before we give up on them
     * @see defer()
     * @see _applyChanges()
     */
    public $maxDefers = 500;

    /**
     * @var bool Whether the project config is read-only.
     */
    public $readOnly = false;

    /**
     * @var bool Whether events generated by config changes should be muted.
     * @since 3.1.2
     */
    public $muteEvents = false;

    /**
     * @var bool Whether project config should force updates on entries that aren't new or being removed.
     */
    public $forceUpdate = false;

    /**
     * @var array Map of paths being processed and their original loaded values.
     * @since 3.4.0
     */
    private $_oldValuesByPath = [];

    /**
     * @var array A list of already parsed change paths
     */
    private $_parsedChanges = [];

    /**
     * @var array An array holding the currently applied config. As opposed to yaml files and internal config, this array
     * holds the state of applied-but-not-yet-saved config.
     */
    private $_appliedConfig = [];

    /**
     * @var array A list of all config files, defined by import directives in configuration files.
     */
    private $_configFileList = [];

    /**
     * @var bool Whether the config has been modified during the request and must be saved.
     */
    private $_isConfigModified = false;

    /**
     * @var bool Whether the config should be saved to yaml file at the end of request
     */
    private $_updateConfig = false;

    /**
     * @var bool Whether we’re listening for the request end, to update the Yaml caches.
     * @see updateParsedConfigTimes()
     */
    private $_waitingToUpdateParsedConfigTimes = false;

    /**
     * @var bool Whether project.yaml changes are currently being applied.
     * @see applyYamlChanges()
     * @see getIsApplyingYamlChanges()
     */
    private $_applyingYamlChanges = false;

    /**
     * @var bool Whether the config's dateModified timestamp has been updated by this request.
     */
    private $_timestampUpdated = false;

    /**
     * @var array The current changeset being applied, if applying changes by array.
     */
    private $_changesBeingApplied;

    /**
     * @var array Deferred config sync events
     * @see defer()
     * @see _applyChanges()
     */
    private $_deferredEvents = [];

    /**
     * A running list of all the changes applied during this request
     *
     * @var array
     */
    private $_appliedChanges = [];

    /**
     * @var array Current config as stored in database.
     */
    private $_storedConfig;

    /**
     * @var array The currently-loaded config, possibly with pending changes
     * that will be stored in the database & project.yaml at the end of the request
     */
    private $_loadedConfig;

    /**
     * @var array[] Config change handlers
     * @see registerChangeEventHandler()
     * @see handleChangeEvent()
     * @since 3.4.0
     */
    private $_changeEventHandlers = [];

    /**
     * @var array[] The specificity of change event handlers.
     * @see registerChangeEventHandler()
     * @see handleChangeEvent()
     * @see _sortChangeEventHandlers()
     * @since 3.4.0
     */
    private $_changeEventHandlerSpecificity = [];

    /**
     * @var array[] The registration order of change event handlers.
     * @see registerChangeEventHandler()
     * @see handleChangeEvent()
     * @see _sortChangeEventHandlers()
     * @since 3.4.0
     */
    private $_changeEventHandlerRegistrationOrder = [];

    /**
     * @var bool[] Whether the change event handlers have been sorted.
     * @see registerChangeEventHandler()
     * @see handleChangeEvent()
     * @see _sortChangeEventHandlers()
     * @since 3.4.0
     */
    private $_sortedChangeEventHandlers = [];

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        if (isset($config['maxBackups'])) {
            $config['maxDeltas'] = ArrayHelper::remove($config, 'maxBackups');
            Craft::$app->getDeprecator()->log(__CLASS__ . '::maxBackups', __CLASS__ . '::maxBackups has been deprecated. Use \'maxDeltas\' instead.');
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        Craft::$app->on(Application::EVENT_AFTER_REQUEST, [$this, 'saveModifiedConfigData'], null, false);

        $this->on(self::EVENT_ADD_ITEM, [$this, 'handleChangeEvent']);
        $this->on(self::EVENT_UPDATE_ITEM, [$this, 'handleChangeEvent']);
        $this->on(self::EVENT_REMOVE_ITEM, [$this, 'handleChangeEvent']);

        parent::init();
    }

    /**
     * Resets the internal state.
     *
     * @internal
     */
    public function reset()
    {
        $this->_storedConfig = null;
        $this->_loadedConfig = null;
        $this->_parsedChanges = [];
        $this->_appliedConfig = [];
        $this->_configFileList = [];
        $this->_isConfigModified = false;
        $this->_updateConfig = false;
        $this->_applyingYamlChanges = false;
        $this->_timestampUpdated = false;
        $this->_changesBeingApplied = null;

        $this->init();
    }

    /**
     * Returns a config item value value by its path.
     *
     * ---
     *
     * ```php
     * $value = Craft::$app->projectConfig->get('foo.bar');
     * ```
     *
     * @param string|null $path The config item path, or `null` if the entire config should be returned
     * @param bool $getFromYaml whether data should be fetched from the project config files instead of the loaded config. Defaults to `false`.
     * @return mixed The config item value
     */
    public function get(string $path = null, $getFromYaml = false)
    {
        if ($getFromYaml) {
            $source = $this->_changesBeingApplied ?? $this->_getConfigurationFromYaml();
        } else {
            $source = $this->_getLoadedConfig();
        }

        if ($path === null) {
            return $source;
        }

        return $this->_traverseDataArray($source, $path);
    }

    /**
     * Sets a config item value at the given path.
     *
     * ---
     *
     * ```php
     * Craft::$app->projectConfig->set('foo.bar', 'value');
     * ```
     *
     * @param string $path The config item path
     * @param mixed $value The config item value
     * @param string|null $message The message describing changes.
     * @throws NotSupportedException if the service is set to read-only mode
     * @throws ErrorException
     * @throws Exception
     * @throws ServerErrorHttpException
     */
    public function set(string $path, $value, $message = '')
    {
        if (\is_array($value)) {
            $value = ProjectConfigHelper::cleanupConfig($value);
        }

        $valueChanged = false;

        if ($value !== $this->get($path)) {
            if ($this->readOnly) {
                // If we're applying yaml changes that are coming in via `project.yaml`, anyway, bail silently.
                if ($this->getIsApplyingYamlChanges() && $value === $this->get($path, true)) {
                    return;
                }

                throw new NotSupportedException('Changes to the project config are not possible while in read-only mode.');
            }

            if (!$this->_timestampUpdated) {
                $this->_timestampUpdated = true;
                $this->set('dateModified', DateTimeHelper::currentTimeStamp(), 'Update timestamp for project config');
            }

            $valueChanged = true;
        }

        // Mark this path (and its parent paths) as being processed, and store their current values
        // Ensure that new data is processed for this path and all its parent paths
        $tok = strtok($path, '.');
        $thisPath = '';
        while ($tok !== false) {
            $thisPath .= ($thisPath !== '' ? '.' : '') . $tok;
            $this->_oldValuesByPath[$thisPath] = $this->get($thisPath);
            unset($this->_parsedChanges[$thisPath]);
            $tok = strtok('.');
        }

        // Save config only if something actually changed.
        if ($valueChanged) {
            $config = $this->_getConfigurationFromYaml();
            $this->_traverseDataArray($config, $path, $value, $value === null);
            $this->_saveConfig($config);
        }

        $this->processConfigChanges($path, true, $message);
    }

    /**
     * Removes a config item at the given path.
     *
     * ---
     * ```php
     * Craft::$app->projectConfig->remove('foo.bar');
     * ```
     *
     * @param string $path The config item path
     * @param string|null $message The message describing changes.
     */
    public function remove(string $path, string $message = null)
    {
        $this->set($path, null, $message);
    }

    /**
     * Regenerates `project.yaml` based on the loaded project config.
     */
    public function regenerateYamlFromConfig()
    {
        $loadedConfig = $this->_getLoadedConfig();
        $this->_saveConfig($loadedConfig);
        $this->updateParsedConfigTimesAfterRequest();
        $this->saveModifiedConfigData();
    }

    /**
     * Applies changes in `project.yaml` to the project config.
     */
    public function applyYamlChanges()
    {
        $mutex = Craft::$app->getMutex();
        $lockName = 'project-config-sync';

        if (!$mutex->acquire($lockName, 15)) {
            throw new Exception('Could not acquire a lock for the syncing project config.');
        }

        // Disable read/write splitting for the remainder of this request
        Craft::$app->getDb()->enableReplicas = false;

        $this->_applyingYamlChanges = true;
        Craft::$app->getCache()->delete(self::CACHE_KEY);

        $changes = $this->_getPendingChanges();

        $this->_applyChanges($changes);

        // Kill the cached config data
        Craft::$app->getCache()->delete(self::STORED_CACHE_KEY);

        $mutex->release($lockName);
    }

    /**
     * Applies given changes to the project config.
     *
     * @param array $configData
     */
    public function applyConfigChanges(array $configData)
    {
        $this->_applyingYamlChanges = true;

        $changes = $this->_getPendingChanges($configData);

        $this->_changesBeingApplied = $configData;
        $this->_applyChanges($changes);
        $this->_changesBeingApplied = null;

        // Cover an edge-case where we're applying changes, but there's no config file yet
        if (empty($this->_appliedConfig)) {
            $this->_appliedConfig = $configData;
        }
    }

    /**
     * Returns whether project.yaml changes are currently being applied
     *
     * @return bool
     */
    public function getIsApplyingYamlChanges(): bool
    {
        return $this->_applyingYamlChanges;
    }

    /**
     * Returns whether a given path has pending changes that need to be applied to the loaded project config.
     *
     * @param string|null $path A specific config path that should be checked for pending changes.
     * If this is null, then `true` will be returned if there are *any* pending changes in `project.yaml.`.
     * @return bool
     */
    public function areChangesPending(string $path = null): bool
    {
        // If the path is currently being processed, return true
        if ($path !== null && array_key_exists($path, $this->_oldValuesByPath)) {
            return true;
        }

        // TODO remove after next breakpoint
        if (version_compare(Craft::$app->getInfo()->schemaVersion, '3.4.4', '<')) {
            return false;
        }

        // If the file does not exist, but should, generate it
        if (!file_exists(Craft::$app->getPath()->getProjectConfigFilePath())) {
            $this->regenerateYamlFromConfig();
            $this->saveModifiedConfigData();
        }

        if (!$this->_areConfigFilesModified()) {
            return false;
        }

        if ($path !== null) {
            $storedConfig = $this->_getStoredConfig();
            $oldValue = $this->_traverseDataArray($storedConfig, $path);
            $newValue = $this->get($path, true);
            return $this->encodeValueAsString($oldValue) !== $this->encodeValueAsString($newValue);
        }

        $changes = $this->_getPendingChanges();

        foreach ($changes as $changeType) {
            if (!empty($changeType)) {
                // Clear the cached config, just in case it conflicts with what we've got here
                Craft::$app->getCache()->delete(self::STORED_CACHE_KEY);
                $this->_loadedConfig = null;
                return true;
            }
        }

        $this->updateParsedConfigTimes();

        return false;
    }

    /**
     * Processes changes in the project config files for a given config item path.
     *
     * @param string $path The config item path
     * @param bool $triggerUpdate is set to true and no changes are detected, an update event will be triggered, anyway.
     * @param string|null $message The message describing changes, if modifications are made.
     * @param bool $force Whether the config change should be processed regardless of previous records
     */
    public function processConfigChanges(string $path, bool $triggerUpdate = false, $message = null, bool $force = false)
    {
        if (!$force && !empty($this->_parsedChanges[$path])) {
            return;
        }

        $this->_parsedChanges[$path] = true;

        $storedConfig = $this->_getStoredConfig();
        $oldValue = $this->_traverseDataArray($storedConfig, $path);

        // If this path is currently being processed, use its original pre-processed value as the "old" value
        foreach ($this->_oldValuesByPath as $thisPath => $thisOldValue) {
            if (strpos("$path.", "$thisPath.") === 0) {
                if ($path === $thisPath) {
                    $oldValue = $thisOldValue;
                } else if (is_array($thisOldValue)) {
                    $oldValue = $this->_traverseDataArray($thisOldValue, substr($path, strlen($thisPath) + 1));
                } else {
                    $oldValue = null;
                }
                break;
            }
        }

        $newValue = $this->get($path, true);
        $valueChanged = $triggerUpdate || $this->forceUpdate || $this->encodeValueAsString($oldValue) !== $this->encodeValueAsString($newValue);

        if ($valueChanged && !$this->muteEvents) {
            $event = new ConfigEvent(compact('path', 'oldValue', 'newValue'));
            if ($newValue === null && $oldValue !== null) {
                // Fire a 'removeItem' event
                $this->trigger(self::EVENT_REMOVE_ITEM, $event);
            } else if ($oldValue === null && $newValue !== null) {
                // Fire an 'addItem' event
                $this->trigger(self::EVENT_ADD_ITEM, $event);
            } else {
                // Fire an 'updateItem' event
                $this->trigger(self::EVENT_UPDATE_ITEM, $event);
            }
        }

        // Mark this path, and any parent paths, as parsed
        $tok = strtok($path, '.');
        $thisPath = '';
        while ($tok !== false) {
            $thisPath .= ($thisPath !== '' ? '.' : '') . $tok;
            unset($this->_oldValuesByPath[$thisPath]);
            $this->_parsedChanges[$thisPath] = true;
            $tok = strtok('.');
        }

        if ($valueChanged) {
            // Memoize the new config data
            $this->_updateInternalConfig($path, $oldValue, $newValue, $message);

            $this->updateStoredConfigAfterRequest();
            $this->updateParsedConfigTimesAfterRequest();
        }
    }

    /**
     * Updates the stored config after the request ends.
     */
    public function updateStoredConfigAfterRequest()
    {
        $this->_updateConfig = true;
    }

    /**
     * Updates cached config file modified times after the request ends.
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
     * Updates cached config file modified times immediately.
     *
     * @return bool
     */
    public function updateParsedConfigTimes(): bool
    {
        return Craft::$app->getCache()->set(self::CACHE_KEY, $this->_getConfigFileModifiedTime(), self::CACHE_DURATION);
    }

    /**
     * Saves all the config data that has been modified up to now.
     *
     * @throws ErrorException
     */
    public function saveModifiedConfigData()
    {
        if ($this->_isConfigModified) {
            $this->_updateYamlFiles();
        }

        if (!$this->_updateConfig) {
            return;
        }

        if (!empty($this->_appliedChanges)) {
            $deltaEntry = [
                'dateApplied' => date('Y-m-d H:i:s'),
                'changes' => []
            ];

            $db = Craft::$app->getDb();
            foreach ($this->_appliedChanges as $changeSet) {
                // Allow modification of the array being looped over.
                $currentSet = $changeSet;

                if (!empty($changeSet['removed'])) {
                    Db::delete(Table::PROJECTCONFIG, [
                        'path' => array_keys($changeSet['removed']),
                    ]);
                }

                if (!empty($changeSet['added'])) {
                    $isMysql = $db->getIsMysql();
                    $batch = [];
                    $pathsToInsert = [];
                    $additionalCleanupPaths = [];

                    foreach ($currentSet['added'] as $key => $value) {
                        // Prepare for storage
                        $dbValue = $this->encodeValueAsString($value);
                        if (!mb_check_encoding($value, 'UTF-8') || ($isMysql && StringHelper::containsMb4($dbValue))) {
                            $dbValue = 'base64:' . base64_encode($dbValue);
                        }
                        $batch[] = [$key, $dbValue];
                        $pathsToInsert[] = $key;

                        // Delete parent key, as it cannot hold a value AND be an array at the same time
                        $additionalCleanupPaths[pathinfo($key, PATHINFO_FILENAME)] = true;

                        // Prepare for delta
                        if (!empty($currentSet['removed']) && array_key_exists($key, $currentSet['removed'])) {
                            if (is_string($changeSet['removed'][$key])) {
                                $changeSet['removed'][$key] = StringHelper::decdec($changeSet['removed'][$key]);
                            }

                            $changeSet['removed'][$key] = Json::decodeIfJson($changeSet['removed'][$key]);

                            // Ensure types
                            if (is_bool($value)) {
                                $changeSet['removed'][$key] = (bool)$changeSet['removed'][$key];
                            } else if (is_int($value)) {
                                $changeSet['removed'][$key] = (int)$changeSet['removed'][$key];
                            }

                            if ($changeSet['removed'][$key] === $value) {
                                unset($changeSet['removed'][$key], $changeSet['added'][$key]);
                            } elseif (array_key_exists($key, $changeSet['removed'])) {
                                $changeSet['changed'][$key] = [
                                    'from' => $changeSet['removed'][$key],
                                    'to' => $changeSet['added'][$key],
                                ];

                                unset($changeSet['removed'][$key], $changeSet['added'][$key]);
                            }
                        }
                    }

                    // Store in the DB
                    if (!empty($batch)) {
                        Db::delete(Table::PROJECTCONFIG, [
                            'path' => $pathsToInsert,
                        ]);
                        Db::delete(Table::PROJECTCONFIG, [
                            'path' => array_keys($additionalCleanupPaths),
                        ]);
                        Db::batchInsert(Table::PROJECTCONFIG, ['path', 'value'], $batch, false);
                    }
                }

                if (empty($changeSet['added'])) {
                    unset($changeSet['added']);
                }

                if (empty($changeSet['removed'])) {
                    unset($changeSet['removed']);
                }

                if (!empty($changeSet['added']) || !empty($changeSet['removed']) || !empty($changeSet['changed'])) {
                    $deltaEntry['changes'][] = $changeSet;
                }
            }

            if (!empty($deltaEntry['changes'])) {
                $this->_storeYamlHistory($deltaEntry);
            }
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
     * The schemas must match exactly to avoid unpredictable behavior that can occur when running migrations
     * and applying project config changes at the same time.
     *
     * @param array $issues Passed by reference and populated with issues on error in
     *                      the following format: `[$pluginName, $existingSchema, $incomingSchema]`
     *
     * @return bool|array
     */
    public function getAreConfigSchemaVersionsCompatible(&$issues = [])
    {
        // TODO remove after next breakpoint
        if (version_compare(Craft::$app->getInfo()->version, '3.1', '<')) {
            return true;
        }

        $incomingSchema = (string)$this->get(self::CONFIG_SCHEMA_VERSION_KEY, true);
        $existingSchema = (string)Craft::$app->schemaVersion;

        // Compare existing Craft schema version with the one that is being applied.
        if (!version_compare($existingSchema, $incomingSchema, '=')) {
            $issues[] = [
                'cause' => 'Craft CMS',
                'existing' => $existingSchema,
                'incoming' => $incomingSchema
            ];
        }

        $plugins = Craft::$app->getPlugins()->getAllPlugins();

        foreach ($plugins as $plugin) {
            $incomingSchema = (string)$this->get(Plugins::CONFIG_PLUGINS_KEY . '.' . $plugin->handle . '.schemaVersion', true);
            $existingSchema = (string)$plugin->schemaVersion;

            // Compare existing plugin schema version with the one that is being applied.
            if ($incomingSchema && !version_compare($existingSchema, $incomingSchema, '=')) {
                $issues[] = [
                    'cause' => $plugin->name,
                    'existing' => $existingSchema,
                    'incoming' => $incomingSchema
                ];
            }
        }

        return empty($issues);
    }

    // Config Change Event Registration
    // -------------------------------------------------------------------------

    /**
     * Attaches an event handler for when an item is added to the config at a given path.
     *
     * ---
     *
     * ```php
     * use craft\events\ConfigEvent;
     * use craft\helpers\Db;
     *
     * Craft::$app->projectConfig->onAdd('foo.{uid}', function(ConfigEvent $event) {
     *     // Get the UID from the item path
     *     $uid = $event->tokenMatches[0];
     *
     *     // Prep the row data
     *     $data = array_merge($event->newValue);
     *
     *     // See if the row already exists (maybe it was soft-deleted)
     *     $id = Db::idByUid('{{%tablename}}', $uid);
     *
     *     if ($id) {
     *         $data['dateDeleted'] = null;
     *         Craft::$app->db->createCommand()->update('{{%tablename}}', $data, [
     *             'id' => $id,
     *         ]);
     *     } else {
     *         $data['uid'] = $uid;
     *         Craft::$app->db->createCommand()->insert('{{%tablename}}', $data);
     *     }
     * });
     * ```
     *
     * @param string $path The config path pattern. Can contain `{uri}` tokens, which will be passed to the handler.
     * @param callable $handler The handler method.
     * @param mixed $data The data to be passed to the event handler when the event is triggered.
     * When the event handler is invoked, this data can be accessed via [[ConfigEvent::data]].
     * @return static self reference
     */
    public function onAdd(string $path, $handler, $data = null): self
    {
        $this->registerChangeEventHandler(self::EVENT_ADD_ITEM, $path, $handler, $data);
        return $this;
    }

    /**
     * Attaches an event handler for when an item is updated in the config at a given path.
     *
     * ---
     *
     * ```php
     * use craft\events\ConfigEvent;
     *
     * Craft::$app->projectConfig->onUpdate('foo.{uid}', function(ConfigEvent $event) {
     *     // Get the UID from the item path
     *     $uid = $event->tokenMatches[0];
     *
     *     // Update the item in the database
     *     $data = array_merge($event->newValue);
     *     Craft::$app->db->createCommand()->update('{{%tablename}}', $data, [
     *         'uid' => $uid,
     *     ]);
     * });
     * ```
     *
     * @param string $path The config path pattern. Can contain `{uri}` tokens, which will be passed to the handler.
     * @param callable $handler The handler method.
     * @param mixed $data The data to be passed to the event handler when the event is triggered.
     * When the event handler is invoked, this data can be accessed via [[ConfigEvent::data]].
     * @return static self reference
     */
    public function onUpdate(string $path, $handler, $data = null): self
    {
        $this->registerChangeEventHandler(self::EVENT_UPDATE_ITEM, $path, $handler, $data);
        return $this;
    }

    /**
     * Attaches an event handler for when an item is removed from the config at a given path.
     *
     * ---
     *
     * ```php
     * use craft\events\ConfigEvent;
     *
     * Craft::$app->projectConfig->onRemove('foo.{uid}', function(ConfigEvent $event) {
     *     // Get the UID from the item path
     *     $uid = $event->tokenMatches[0];
     *
     *     // Soft-delete the item from the database
     *     Craft::$app->db->createCommand()->softDelete('{{%tablename}}', [
     *         'uid' => $uid,
     *     ]);
     * });
     * ```
     *
     * @param string $path The config path pattern. Can contain `{uri}` tokens, which will be passed to the handler.
     * @param callable $handler The handler method.
     * @param mixed $data The data to be passed to the event handler when the event is triggered.
     * When the event handler is invoked, this data can be accessed via [[ConfigEvent::data]].
     * @return static self reference
     */
    public function onRemove(string $path, $handler, $data = null): self
    {
        $this->registerChangeEventHandler(self::EVENT_REMOVE_ITEM, $path, $handler, $data);
        return $this;
    }

    /**
     * Defers an event until all other project config changes have been processed.
     *
     * @param ConfigEvent $event
     * @param callable $handler
     * @since 3.1.13
     */
    public function defer(ConfigEvent $event, callable $handler)
    {
        Craft::info('Deferring event handler for ' . $event->path, __METHOD__);
        $this->_deferredEvents[] = [$event, $event->tokenMatches, $handler];
    }

    /**
     * Registers a config change event listener, for a specific config path pattern.
     *
     * @param string $event The event name
     * @param string $path The config path pattern. Can contain `{uid}` tokens, which will be passed to the handler.
     * @param callable $handler The handler method.
     * @param mixed $data The data to be passed to the event handler when the event is triggered.
     * When the event handler is invoked, this data can be accessed via [[ConfigEvent::data]].
     */
    public function registerChangeEventHandler(string $event, string $path, $handler, $data = null)
    {
        $specificity = substr_count($path, '.');
        $pattern = '/^(?P<path>' . preg_quote($path, '/') . ')(?P<extra>\..+)?$/';
        $pattern = str_replace('\\{uid\\}', '(' . self::UID_PATTERN . ')', $pattern);

        $this->_changeEventHandlers[$event][] = [$pattern, $handler, $data];
        $this->_changeEventHandlerSpecificity[$event][] = $specificity;
        $this->_changeEventHandlerRegistrationOrder[$event][] = count($this->_changeEventHandlers[$event]);
        unset($this->_sortedChangeEventHandlers[$event]);
    }

    /**
     * Handles a config change event.
     *
     * @param ConfigEvent $event
     * @since 3.4.0
     */
    public function handleChangeEvent(ConfigEvent $event)
    {
        if (empty($this->_changeEventHandlers[$event->name])) {
            return;
        }

        // Make sure the event handlers are sorted from least-to-most specific
        $this->_sortChangeEventHandlers($event->name);

        foreach ($this->_changeEventHandlers[$event->name] as list($pattern, $handler, $data)) {
            if (preg_match($pattern, $event->path, $matches)) {
                // Is this a nested path?
                if (isset($matches['extra'])) {
                    $this->processConfigChanges($matches['path']);
                    continue;
                }

                // Chop off [0] (full match) and ['path'] & [1] (requested path)
                $event->tokenMatches = array_values(array_slice($matches, 3));

                // Set the event data
                $event->data = $data;

                $handler($event);

                $event->tokenMatches = null;
                $event->data = null;
            }
        }
    }

    /**
     * Ensures that the config change event handlers are sorted by least-to-most specific.
     *
     * @param string $event The event name
     * @since 3.4.0
     */
    private function _sortChangeEventHandlers(string $event)
    {
        if (isset($this->_sortedChangeEventHandlers[$event])) {
            return;
        }

        array_multisort(
            $this->_changeEventHandlerSpecificity[$event], SORT_ASC, SORT_NUMERIC,
            $this->_changeEventHandlerRegistrationOrder[$event], SORT_ASC, SORT_NUMERIC,
            $this->_changeEventHandlers[$event]);

        $this->_sortedChangeEventHandlers[$event] = true;
    }

    /**
     * Rebuilds the project config from the current state in the database.
     *
     * @throws \Throwable if reasons
     * @since 3.1.20
     */
    public function rebuild()
    {
        $this->reset();
        $currentConfig = $this->get();

        // Gather everything that we can about the current state of affairs
        $configData = $this->_getCurrentStateData();

        // Fire a 'rebuild' event
        $event = new RebuildConfigEvent([
            'config' => $configData,
        ]);
        $this->trigger(self::EVENT_REBUILD, $event);

        // Remove any existing user groups and fieldlayouts from $currentConfig
        unset($currentConfig['users']['groups'], $currentConfig['users']['fieldLayouts']);

        // Merge the new data over the existing one.
        $configData = array_replace_recursive([
            'system' => $currentConfig['system'],
            'routes' => $currentConfig['routes'] ?? [],
            'plugins' => $currentConfig['plugins'] ?? [],
            'users' => $currentConfig['users'] ?? [],
            'email' => $currentConfig['email'] ?? [],
        ], $event->config);

        $this->muteEvents = true;
        $readOnly = $this->readOnly;
        $this->readOnly = false;

        foreach ($configData as $path => $value) {
            $this->set($path, $value, 'Project config rebuild');
        }

        $this->_appliedConfig = $configData;

        $this->readOnly = $readOnly;
        $this->muteEvents = false;
    }

    /**
     * Applies changes from a configuration array.
     *
     * @param array $changes array nested array with keys `removedItems`, `changedItems` and `newItems`
     * @throws OperationAbortedException
     */
    private function _applyChanges(array $changes)
    {
        Craft::info('Looking for pending changes', __METHOD__);

        // If we're parsing all the changes, we better work the actual config map.
        if (!empty($changes['removedItems'])) {
            Craft::info('Parsing ' . count($changes['removedItems']) . ' removed configuration items', __METHOD__);
            foreach ($changes['removedItems'] as $itemPath) {
                $this->processConfigChanges($itemPath, false, null, true);
            }
        }

        if (!empty($changes['changedItems'])) {
            Craft::info('Parsing ' . count($changes['changedItems']) . ' changed configuration items', __METHOD__);
            foreach ($changes['changedItems'] as $itemPath) {
                $this->processConfigChanges($itemPath, false, null, true);
            }
        }

        if (!empty($changes['newItems'])) {
            Craft::info('Parsing ' . count($changes['newItems']) . ' new configuration items', __METHOD__);
            foreach ($changes['newItems'] as $itemPath) {
                $this->processConfigChanges($itemPath, false, null, true);
            }
        }

        $defers = -count($this->_deferredEvents);
        while (!empty($this->_deferredEvents)) {
            if ($defers > $this->maxDefers) {
                $paths = [];

                // Grab a list of all deferred event paths
                foreach ($this->_deferredEvents as list($deferredEvent)) {
                    // Save us the trouble of filtering out duplicates later
                    $paths[$deferredEvent->path] = true;
                }

                $message = "The following config paths could not be processed successfully:\n" . implode("\n", array_keys($paths));
                throw new OperationAbortedException($message);
            }

            /** @var ConfigEvent $event */
            /** @var string[]|null $tokenMatches */
            /** @var callable $handler */
            list($event, $tokenMatches, $handler) = array_shift($this->_deferredEvents);
            Craft::info('Re-triggering deferred event for ' . $event->path, __METHOD__);
            $event->tokenMatches = $tokenMatches;
            $handler($event);
            $event->tokenMatches = null;
            $defers++;
        }

        Craft::info('Finalizing configuration parsing', __METHOD__);

        // Fire an 'afterApplyChanges' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_APPLY_CHANGES)) {
            $this->trigger(self::EVENT_AFTER_APPLY_CHANGES);
        }

        $this->updateParsedConfigTimesAfterRequest();
        $this->_applyingYamlChanges = false;
    }

    /**
     * Retrieve a a config file tree with modified times based on the main configuration file.
     *
     * @return int
     */
    private function _getConfigFileModifiedTime(): int
    {
        return FileHelper::lastModifiedTime(Craft::$app->getPath()->getProjectConfigPath());
    }

    /**
     * Generate the configuration based on the configuration files.
     *
     * @return array
     */
    private function _getConfigurationFromYaml(): array
    {
        if (!empty($this->_appliedConfig)) {
            return $this->_appliedConfig;
        }

        $fileList = $this->_getConfigFileList();
        $generatedConfig = [];

        foreach ($fileList as $filePath) {
            $yamlConfig = Yaml::parse(file_get_contents($filePath));
            $subPath = StringHelper::removeLeft($filePath, Craft::$app->getPath()->getProjectConfigPath() . DIRECTORY_SEPARATOR);

            if (StringHelper::countSubstrings($subPath, '/') > 0) {
                $configPath = explode("/", $subPath);
                $filename = pathinfo(array_pop($configPath), PATHINFO_FILENAME);
                $insertionPoint = &$generatedConfig;

                foreach ($configPath as $pathSegment) {
                    if (!isset($insertionPoint[$pathSegment])) {
                        $insertionPoint[$pathSegment] = [];
                    }

                    $insertionPoint = &$insertionPoint[$pathSegment];
                }

                if ($pathSegment === $filename) {
                    $insertionPoint = array_merge($insertionPoint, $yamlConfig);
                } else {
                    $insertionPoint[$filename] = $yamlConfig;
                }
            } else {
                $generatedConfig = array_merge($generatedConfig, $yamlConfig);
            }
        }

        $this->_appliedConfig = $generatedConfig;

        return $generatedConfig ?? [];
    }

    /**
     * Return a nested array for pending config changes
     *
     * @param array $configData config data to use. If null, the config is fetched from the project config files.
     * @return array
     */
    private function _getPendingChanges(array $configData = null): array
    {
        $newItems = [];
        $changedItems = [];

        $currentConfig = $this->_getLoadedConfig() ?? [];

        if ($configData === null) {
            $configData = $this->_getConfigurationFromYaml() ?? [];
            unset($configData['dateModified'], $currentConfig['dateModified']);
        }

        unset($configData['imports'], $currentConfig['imports']);

        // flatten both configs so we can compare them.
        $flatConfig = [];
        $flatCurrent = [];

        ProjectConfigHelper::flattenConfigArray($configData, '', $flatConfig);
        ProjectConfigHelper::flattenConfigArray($currentConfig, '', $flatCurrent);

        // Compare and if something is different, mark the immediate parent as changed.
        foreach ($flatConfig as $key => $value) {
            // Drop the last part of path
            $immediateParent = pathinfo($key, PATHINFO_FILENAME);

            if (!array_key_exists($key, $flatCurrent)) {
                $newItems[] = $immediateParent;
            } else if ($this->forceUpdate || $flatCurrent[$key] !== $value) {
                $changedItems[] = $immediateParent;
            }

            unset($flatCurrent[$key]);
        }

        $removedItems = array_keys($flatCurrent);

        foreach ($removedItems as &$removedItem) {
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

        $newItems = array_unique($newItems);
        $removedItems = array_unique($removedItems);
        $changedItems = array_unique($changedItems);

        uasort($newItems, $sorter);
        uasort($removedItems, $sorter);
        uasort($changedItems, $sorter);

        return compact('newItems', 'removedItems', 'changedItems');
    }

    /**
     * Return true if the config files have been modified since last we checked.
     *
     * @return bool
     */
    private function _areConfigFilesModified(): bool
    {
        $cachedModifiedTime = Craft::$app->getCache()->get(self::CACHE_KEY);

        if (empty($cachedModifiedTime)) {
            return true;
        }

        $pathService = Craft::$app->getPath();

        // Check whether we have a missing main config file or any of the sub-files have been modified.
        if (!file_exists($pathService->getProjectConfigFilePath()) || $this->_getConfigFileModifiedTime() != $cachedModifiedTime) {
            return true;
        }

        // Re-cache
        Craft::$app->getCache()->set(self::CACHE_KEY, $cachedModifiedTime, self::CACHE_DURATION);

        return false;
    }

    /**
     * Figure out the entire list of yaml config files
     *
     * @return array
     */
    private function _getConfigFileList(): array
    {
        if (!empty($this->_configFileList)) {
            return $this->_configFileList;
        }

        return $this->_configFileList = $this->_findConfigFiles();
    }

    /**
     * Finds all of the `.yaml` files in the `config/project/` folder.
     *
     * @param string|null $path
     * @return string[]
     */
    private function _findConfigFiles(string $path = null): array
    {
        if ($path === null) {
            $path = Craft::$app->getPath()->getProjectConfigPath();
        }
        return FileHelper::findFiles($path, [
            'only' => ['*.yaml'],
            'caseSensitive' => false
        ]);
    }

    /**
     * Save configuration data.
     *
     * @param array $data
     * @throws ErrorException
     */
    private function _saveConfig(array $data)
    {
        $this->_appliedConfig = $data;
        $this->_isConfigModified = true;
    }

    /**
     * Whether to use the config file or not.
     *
     * @return bool
     */
    private function _useConfigFile(): bool
    {
        return true;
    }

    /**
     * Traverse a nested data array according to path and perform an action depending on parameters.
     *
     * @param array $data A nested array of data to traverse
     * @param array|string $path Path used to traverse the array. Either an array or a dot.based.path
     * @param mixed $value Value to set at the destination. If null, will return the value, unless deleting
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
            } else if ($value === null) {
                return $data[$nextSegment] ?? null;
            } else {
                $data[$nextSegment] = $value;
            }
        } else {
            if (!isset($data[$nextSegment])) {
                // If the path doesn't exist, it's fine if we wanted to delete or read
                if ($delete || $value === null) {
                    return null;
                }

                $data[$nextSegment] = [];
            } else if (!is_array($data[$nextSegment])) {
                // If the next part is not an array, but we have to travel further, make it an array.
                $data[$nextSegment] = [];
            }

            return $this->_traverseDataArray($data[$nextSegment], $path, $value, $delete);
        }

        return null;
    }

    /**
     * Store yaml history
     *
     * @param array $configData config data to be saved as history
     * @throws Exception
     */
    private function _storeYamlHistory(array $configData)
    {
        $basePath = Craft::$app->getPath()->getConfigDeltaPath() . '/' . self::CONFIG_DELTA_FILENAME;

        // Go through all of them and move them forward.
        for ($i = $this->maxDeltas; $i > 0; $i--) {
            $thisFile = $basePath . ($i == 1 ? '' : '.' . ($i - 1));
            if (file_exists($thisFile)) {
                if ($i === $this->maxDeltas) {
                    @unlink($thisFile);
                } else {
                    @rename($thisFile, "$basePath.$i");
                }
            }
        }

        file_put_contents($basePath, Yaml::dump($configData, 20, 2));
    }

    /**
     * Returns the loaded config.
     *
     * @return array
     */
    private function _getLoadedConfig(): array
    {
        // _loadedConfig will be set if we've made any changes in this request
        if ($this->_loadedConfig !== null) {
            return $this->_loadedConfig;
        }

        // Otherwise just return whatever's in the DB
        return $this->_getStoredConfig();
    }

    /**
     * Returns the stored config.
     *
     * @return array
     */
    private function _getStoredConfig(): array
    {
        if ($this->_storedConfig === null) {
            $this->_storedConfig = $this->_loadInternalConfigData();
        }

        return $this->_storedConfig;
    }


    /**
     * Create a Query object ready to retrieve internal project config values.
     *
     * @return Query
     */
    private function _createProjectConfigQuery(): Query
    {
        return (new Query())
            ->select(['path', 'value'])
            ->from([Table::PROJECTCONFIG]);
    }

    /**
     * Update the config Yaml files with the buffered changes.
     *
     * @throws ErrorException
     * @throws \yii\base\InvalidConfigException
     */
    private function _updateYamlFiles()
    {
        $config = ProjectConfigHelper::splitConfigIntoComponents($this->_appliedConfig);
        $basePath = Craft::$app->getPath()->getProjectConfigPath();

        foreach ($config as $relativeFile => $configData) {
            $configData = ProjectConfigHelper::cleanupConfig($configData);
            ksort($configData);
            $filePath = $basePath . DIRECTORY_SEPARATOR . $relativeFile;
            FileHelper::writeToFile($filePath, Yaml::dump($configData, 20, 2));
        }

        // See if there are any files that shouldn’t be around anymore
        $basePathLength = strlen($basePath);
        foreach ($this->_findConfigFiles($basePath) as $file) {
            $path = substr($file, $basePathLength + 1);
            if (!isset($config[$path])) {
                FileHelper::unlink($file);
            }
        }
    }

    /**
     * Update Craft's internal config store for a path with the new value. If the value
     * is null, it will be removed instead.
     *
     * @param string $path
     * @param mixed|null $oldValue
     * @param mixed|null $newValue
     * @param string|null $message message describing the changes made.
     */
    private function _updateInternalConfig(string $path, $oldValue, $newValue, string $message = null)
    {
        $currentLoadedConfig = $this->_getLoadedConfig();
        $this->_traverseDataArray($currentLoadedConfig, $path, $newValue, $newValue === null);
        $this->_loadedConfig = $currentLoadedConfig;

        $appliedChanges = [];

        $modified = $this->encodeValueAsString($oldValue) !== $this->encodeValueAsString($newValue);

        if ($newValue !== null && ($oldValue === null || $modified)) {
            if (!is_scalar($newValue)) {
                $flatData = [];
                ProjectConfigHelper::flattenConfigArray($newValue, $path, $flatData);
            } else {
                $flatData = [$path => $newValue];
            }

            $appliedChanges['added'] = $flatData;
        }

        if ($oldValue && ($newValue === null || $modified)) {
            if (!is_scalar($oldValue)) {
                $flatData = [];
                ProjectConfigHelper::flattenConfigArray($oldValue, $path, $flatData);
            } else {
                $flatData = [$path => $oldValue];
            }

            $appliedChanges['removed'] = $flatData;
        }

        if ($message) {
            $appliedChanges['message'] = $message;
        }

        $this->_appliedChanges[] = $appliedChanges;
    }

    /**
     * Load internal config data by a given path.
     *
     * @param string $path
     * @param array $current
     * @return mixed
     */
    private function _loadInternalConfigData()
    {
        $data = [];

        if (!Craft::$app->getIsInstalled()) {
            return $data;
        }

        if (Craft::$app->getIsInstalled() && version_compare(Craft::$app->getInfo()->schemaVersion, '3.1.1', '<')) {
            return $data;
        }

        if (Craft::$app->getIsInstalled() && version_compare(Craft::$app->getInfo()->schemaVersion, '3.4.4', '<')) {
            $config = (new Query())
                ->select(['config'])
                ->from([Table::INFO])
                ->scalar();

            if ($config) {
                // Try to decode it in case it contains any 4+ byte characters
                $config = StringHelper::decdec($config);
                if (strpos($config, '{') === 0) {
                    $data = Json::decode($config);
                } else {
                    $data = unserialize($config, ['allowed_classes' => false]);
                }
            }

            return $data;
        }

        // See if we can get away with using the cached data
        $dependency = new DbQueryDependency([
            'db' => 'db',
            'query' => $this->_createProjectConfigQuery()
                ->select(['value'])
                ->where(['path' => 'dateModified']),
            'method' => 'scalar'
        ]);

        return Craft::$app->getCache()->getOrSet(self::STORED_CACHE_KEY, function() {
            $data = [];
            // Load the project config data
            $rows = $this->_createProjectConfigQuery()->orderBy('path')->pairs();
            foreach ($rows as $path => $value) {
                $current = &$data;
                $segments = explode('.', $path);
                foreach ($segments as $segment) {
                    // If we're still traversing, enforce array to avoid errors.
                    if (!is_array($current)) {
                        $current = [];
                    }
                    if (!array_key_exists($segment, $current)) {
                        $current[$segment] = [];
                    }
                    $current = &$current[$segment];
                }
                $current = Json::decode(StringHelper::decdec($value));
            }
            return ProjectConfigHelper::cleanupConfig($data);
        }, null, $dependency);
    }

    /**
     * Return project config array.
     * TODO: this is just a reminder that this part *needs* to be kept up-to-date as Craft evolves.
     *
     * @return array
     */
    private function _getCurrentStateData(): array
    {
        $data = [
            'dateModified' => DateTimeHelper::currentTimeStamp(),
            'siteGroups' => $this->_getSiteGroupData(),
            'sites' => $this->_getSiteData(),
            'sections' => $this->_getSectionData(),
            'entryTypes' => $this->_getEntryTypeData(),
            'fieldGroups' => $this->_getFieldGroupData(),
            'fields' => $this->_getFieldData(),
            'matrixBlockTypes' => $this->_getMatrixBlockTypeData(),
            'volumes' => $this->_getVolumeData(),
            'categoryGroups' => $this->_getCategoryGroupData(),
            'tagGroups' => $this->_getTagGroupData(),
            'users' => $this->_getUserData(),
            'globalSets' => $this->_getGlobalSetData(),
            'plugins' => $this->_getPluginData(),
            'imageTransforms' => $this->_getTransformData(),
            'graphql' => $this->_getGqlData(),
        ];

        return $data;
    }

    /**
     * Return site data config array.
     *
     * @return array
     */
    private function _getSiteGroupData(): array
    {
        $data = [];

        $siteGroups = (new Query())
            ->select([
                'uid',
                'name',
            ])
            ->from([Table::SITEGROUPS])
            ->where(['dateDeleted' => null])
            ->pairs();

        foreach ($siteGroups as $uid => $name) {
            $data[$uid] = ['name' => $name];
        }

        return $data;
    }

    /**
     * Return site data config array.
     *
     * @return array
     */
    private function _getSiteData(): array
    {
        $data = [];

        $sites = (new Query())
            ->select([
                'sites.name',
                'sites.handle',
                'sites.language',
                'sites.hasUrls',
                'sites.baseUrl',
                'sites.sortOrder',
                'sites.groupId',
                'sites.uid',
                'sites.primary',
                'siteGroups.uid AS siteGroup',
            ])
            ->from(['sites' => Table::SITES])
            ->innerJoin(['siteGroups' => Table::SITEGROUPS], '[[siteGroups.id]] = [[sites.groupId]]')
            ->where(['sites.dateDeleted' => null])
            ->andWhere(['siteGroups.dateDeleted' => null])
            ->all();

        foreach ($sites as $site) {
            $uid = $site['uid'];
            unset($site['uid'], $site['groupId']);

            $site['sortOrder'] = (int)$site['sortOrder'];
            $site['hasUrls'] = (bool)$site['hasUrls'];
            $site['primary'] = (bool)$site['primary'];

            $data[$uid] = $site;
        }

        return $data;
    }

    /**
     * Return section data config array.
     *
     * @return array
     */
    private function _getSectionData(): array
    {
        $sectionRows = (new Query())
            ->select([
                'sections.id',
                'sections.name',
                'sections.handle',
                'sections.type',
                'sections.enableVersioning',
                'sections.propagationMethod',
                'sections.previewTargets',
                'sections.uid',
                'structures.uid AS structure',
                'structures.maxLevels AS structureMaxLevels',
            ])
            ->from(['sections' => Table::SECTIONS])
            ->leftJoin(['structures' => Table::STRUCTURES], '[[structures.id]] = [[sections.structureId]]')
            ->where(['sections.dateDeleted' => null])
            ->andWhere(['structures.dateDeleted' => null])
            ->all();

        $sectionData = [];

        foreach ($sectionRows as $section) {
            if (!empty($section['structure'])) {
                $section['structure'] = [
                    'uid' => $section['structure'],
                    'maxLevels' => (int)$section['structureMaxLevels'] ?: null,
                ];
            } else {
                unset($section['structure']);
            }

            $uid = $section['uid'];
            unset($section['id'], $section['structureMaxLevels'], $section['uid']);

            $section['enableVersioning'] = (bool)$section['enableVersioning'];

            $sectionData[$uid] = $section;
            $sectionData[$uid]['siteSettings'] = [];
            $sectionData[$uid]['previewTargets'] = Json::decodeIfJson($section['previewTargets']);
        }

        $sectionSiteRows = (new Query())
            ->select([
                'sections_sites.enabledByDefault',
                'sections_sites.hasUrls',
                'sections_sites.uriFormat',
                'sections_sites.template',
                'sites.uid AS siteUid',
                'sections.uid AS sectionUid',
            ])
            ->from(['sections_sites' => Table::SECTIONS_SITES])
            ->innerJoin(['sites' => Table::SITES], '[[sites.id]] = [[sections_sites.siteId]]')
            ->innerJoin(['sections' => Table::SECTIONS], '[[sections.id]] = [[sections_sites.sectionId]]')
            ->where(['sites.dateDeleted' => null])
            ->andWhere(['sections.dateDeleted' => null])
            ->all();

        foreach ($sectionSiteRows as $sectionSiteRow) {
            $sectionUid = $sectionSiteRow['sectionUid'];
            $siteUid = $sectionSiteRow['siteUid'];
            unset($sectionSiteRow['sectionUid'], $sectionSiteRow['siteUid']);

            $sectionSiteRow['hasUrls'] = (bool)$sectionSiteRow['hasUrls'];
            $sectionSiteRow['enabledByDefault'] = (bool)$sectionSiteRow['enabledByDefault'];

            $sectionData[$sectionUid]['siteSettings'][$siteUid] = $sectionSiteRow;
        }

        return $sectionData;
    }

    /**
     * Return entry type data config array.
     *
     * @return array
     */
    private function _getEntryTypeData(): array
    {
        $entryTypeRows = (new Query())
            ->select([
                'entrytypes.fieldLayoutId',
                'entrytypes.name',
                'entrytypes.handle',
                'entrytypes.hasTitleField',
                'entrytypes.titleFormat',
                'entrytypes.sortOrder',
                'entrytypes.uid',
                'sections.uid AS sectionUid',
            ])
            ->from(['entrytypes' => Table::ENTRYTYPES])
            ->innerJoin(['sections' => Table::SECTIONS], '[[sections.id]] = [[entrytypes.sectionId]]')
            ->where(['sections.dateDeleted' => null])
            ->andWhere(['entrytypes.dateDeleted' => null])
            ->all();

        $layoutIds = array_filter(ArrayHelper::getColumn($entryTypeRows, 'fieldLayoutId'));
        $fieldLayouts = $this->_generateFieldLayoutArray($layoutIds);

        $entryTypeData = [];

        foreach ($entryTypeRows as $entryType) {
            $uid = ArrayHelper::remove($entryType, 'uid');
            $entryType['section'] = ArrayHelper::remove($entryType, 'sectionUid');
            $fieldLayoutId = ArrayHelper::remove($entryType, 'fieldLayoutId');

            $entryType['hasTitleField'] = (bool)$entryType['hasTitleField'];
            $entryType['sortOrder'] = (int)$entryType['sortOrder'];

            if ($fieldLayoutId) {
                $layout = array_merge($fieldLayouts[$fieldLayoutId]);
                $layoutUid = ArrayHelper::remove($layout, 'uid');
                $entryType['fieldLayouts'] = [$layoutUid => $layout];
            }

            $entryTypeData[$uid] = $entryType;
        }

        return $entryTypeData;
    }

    /**
     * Return field data config array.
     *
     * @return array
     */
    private function _getFieldGroupData(): array
    {
        $data = [];

        $fieldGroups = (new Query())
            ->select([
                'uid',
                'name',
            ])
            ->from([Table::FIELDGROUPS])
            ->pairs();

        foreach ($fieldGroups as $uid => $name) {
            $data[$uid] = ['name' => $name];
        }

        return $data;
    }

    /**
     * Return field data config array.
     *
     * @return array
     */
    private function _getFieldData(): array
    {
        $data = [];

        $fieldRows = (new Query())
            ->select([
                'fields.id',
                'fields.name',
                'fields.handle',
                'fields.instructions',
                'fields.searchable',
                'fields.translationMethod',
                'fields.translationKeyFormat',
                'fields.type',
                'fields.settings',
                'fields.uid',
                'fieldGroups.uid AS fieldGroup',
            ])
            ->from(['fields' => Table::FIELDS])
            ->leftJoin(['fieldGroups' => Table::FIELDGROUPS], '[[fieldGroups.id]] = [[fields.groupId]]')
            ->where(['fields.context' => 'global'])
            ->all();

        $fields = [];
        $fieldService = Craft::$app->getFields();

        // Massage the data and index by UID
        foreach ($fieldRows as $fieldRow) {
            $fieldRow['settings'] = Json::decodeIfJson($fieldRow['settings']);

            if (is_array($fieldRow['settings'])) {
                $fieldRow['settings'] = ProjectConfigHelper::packAssociativeArrays($fieldRow['settings']);
            }

            $fieldInstance = $fieldService->getFieldById($fieldRow['id']);
            $fieldRow['contentColumnType'] = $fieldInstance->getContentColumnType();

            $fieldRow['searchable'] = (bool)$fieldRow['searchable'];

            $fields[$fieldRow['uid']] = $fieldRow;
        }

        foreach ($fields as $field) {
            $fieldUid = $field['uid'];
            unset($field['id'], $field['uid']);
            $data[$fieldUid] = $field;
        }

        return $data;
    }

    /**
     * Return matrix block type data config array.
     *
     * @return array
     */
    private function _getMatrixBlockTypeData(): array
    {
        $data = [];

        $matrixBlockTypes = (new Query())
            ->select([
                'bt.fieldId',
                'bt.fieldLayoutId',
                'bt.name',
                'bt.handle',
                'bt.sortOrder',
                'bt.uid',
                'f.uid AS field',
            ])
            ->from(['bt' => Table::MATRIXBLOCKTYPES])
            ->innerJoin(['f' => Table::FIELDS], '[[f.id]] = [[bt.fieldId]]')
            ->all();

        $layoutIds = [];
        $blockTypeData = [];

        foreach ($matrixBlockTypes as $matrixBlockType) {
            $fieldId = $matrixBlockType['fieldId'];
            unset($matrixBlockType['fieldId']);

            $layoutIds[] = $matrixBlockType['fieldLayoutId'];

            $matrixBlockType['sortOrder'] = (int)$matrixBlockType['sortOrder'];

            $blockTypeData[$fieldId][$matrixBlockType['uid']] = $matrixBlockType;
        }

        $matrixFieldLayouts = $this->_generateFieldLayoutArray($layoutIds);

        // Fetch the subfields
        $matrixSubfieldRows = (new Query())
            ->select([
                'fields.id',
                'fields.name',
                'fields.handle',
                'fields.instructions',
                'fields.searchable',
                'fields.translationMethod',
                'fields.translationKeyFormat',
                'fields.type',
                'fields.settings',
                'fields.context',
                'fields.uid',
                'fieldGroups.uid AS fieldGroup',
            ])
            ->from(['fields' => Table::FIELDS])
            ->leftJoin(['fieldGroups' => Table::FIELDGROUPS], '[[fieldGroups.id]] = [[fields.groupId]]')
            ->where(['like', 'fields.context', 'matrixBlockType:'])
            ->all();

        $matrixSubFields = [];
        $fieldService = Craft::$app->getFields();

        // Massage the data and index by UID
        foreach ($matrixSubfieldRows as $matrixSubfieldRow) {
            $matrixSubfieldRow['settings'] = Json::decodeIfJson($matrixSubfieldRow['settings']);

            if (is_array($matrixSubfieldRow['settings'])) {
                $matrixSubfieldRow['settings'] = ProjectConfigHelper::packAssociativeArrays($matrixSubfieldRow['settings']);
            }

            $fieldInstance = $fieldService->getFieldById($matrixSubfieldRow['id']);
            $matrixSubfieldRow['contentColumnType'] = $fieldInstance->getContentColumnType();
            list (, $blockTypeUid) = explode(':', $matrixSubfieldRow['context']);

            if (empty($matrixSubFields[$blockTypeUid])) {
                $matrixSubFields[$blockTypeUid] = [];
            }

            $fieldUid = $matrixSubfieldRow['uid'];
            unset($matrixSubfieldRow['uid'], $matrixSubfieldRow['id'], $matrixSubfieldRow['context']);

            $matrixSubfieldRow['searchable'] = (bool)$matrixSubfieldRow['searchable'];

            $matrixSubFields[$blockTypeUid][$fieldUid] = $matrixSubfieldRow;
        }

        foreach ($blockTypeData as &$blockTypes) {
            foreach ($blockTypes as &$blockType) {
                $blockTypeUid = $blockType['uid'];
                $layout = $matrixFieldLayouts[$blockType['fieldLayoutId']];
                unset($blockType['uid'], $blockType['fieldLayoutId']);
                $blockType['fieldLayouts'] = [$layout['uid'] => ['tabs' => $layout['tabs']]];
                $blockType['fields'] = $matrixSubFields[$blockTypeUid] ?? [];
                $data[$blockTypeUid] = $blockType;
            }
        }

        return $data;
    }

    /**
     * Return volume data config array.
     *
     * @return array
     */
    private function _getVolumeData(): array
    {
        $volumes = (new Query())
            ->select([
                'volumes.fieldLayoutId',
                'volumes.name',
                'volumes.handle',
                'volumes.type',
                'volumes.hasUrls',
                'volumes.url',
                'volumes.settings',
                'volumes.sortOrder',
                'volumes.uid',
            ])
            ->from(['volumes' => Table::VOLUMES])
            ->where(['volumes.dateDeleted' => null])
            ->all();

        $layoutIds = [];

        foreach ($volumes as $volume) {
            $layoutIds[] = $volume['fieldLayoutId'];
        }

        $fieldLayouts = $this->_generateFieldLayoutArray($layoutIds);

        $data = [];

        foreach ($volumes as $volume) {
            if (isset($fieldLayouts[$volume['fieldLayoutId']])) {
                $layoutUid = $fieldLayouts[$volume['fieldLayoutId']]['uid'];
                unset($fieldLayouts[$volume['fieldLayoutId']]['uid']);
                $volume['fieldLayouts'] = [$layoutUid => $fieldLayouts[$volume['fieldLayoutId']]];
            }

            $volume['settings'] = Json::decodeIfJson($volume['settings']);

            $uid = $volume['uid'];
            unset($volume['fieldLayoutId'], $volume['uid']);

            $volume['hasUrls'] = (bool)$volume['hasUrls'];
            $volume['sortOrder'] = (int)$volume['sortOrder'];

            $data[$uid] = $volume;
        }

        return $data;
    }

    /**
     * Return user group data config array.
     *
     * @return array
     */
    private function _getUserData(): array
    {
        $data = [];

        $layoutId = (new Query())
            ->select(['id'])
            ->from([Table::FIELDLAYOUTS])
            ->where(['type' => User::class])
            ->andWhere(['dateDeleted' => null])
            ->scalar();

        if ($layoutId) {
            $layouts = array_values($this->_generateFieldLayoutArray([$layoutId]));
            $layout = reset($layouts);
            $uid = $layout['uid'];
            unset($layout['uid']);
            $data['fieldLayouts'] = [$uid => $layout];
        }

        $groups = (new Query())
            ->select(['id', 'name', 'handle', 'uid'])
            ->from([Table::USERGROUPS])
            ->all();

        $permissions = (new Query())
            ->select(['id', 'name'])
            ->from([Table::USERPERMISSIONS])
            ->pairs();

        $groupPermissions = (new Query())
            ->select(['permissionId', 'groupId'])
            ->from([Table::USERPERMISSIONS_USERGROUPS])
            ->all();

        $permissionList = [];

        foreach ($groupPermissions as $groupPermission) {
            $permissionList[$groupPermission['groupId']][] = $permissions[$groupPermission['permissionId']];
        }

        foreach ($groups as $group) {
            $data['groups'][$group['uid']] = [
                'name' => $group['name'],
                'handle' => $group['handle'],
                'permissions' => $permissionList[$group['id']] ?? []
            ];
        }

        return $data;
    }

    /**
     * Return category group data config array.
     *
     * @return array
     */
    private function _getCategoryGroupData(): array
    {
        $groupRows = (new Query())
            ->select([
                'groups.name',
                'groups.handle',
                'groups.uid',
                'groups.fieldLayoutId',
                'structures.uid AS structure',
                'structures.maxLevels AS structureMaxLevels',
            ])
            ->from(['groups' => Table::CATEGORYGROUPS])
            ->leftJoin(['structures' => Table::STRUCTURES], '[[structures.id]] = [[groups.structureId]]')
            ->where(['groups.dateDeleted' => null])
            ->andWhere(['structures.dateDeleted' => null])
            ->all();

        $groupData = [];

        $layoutIds = [];

        foreach ($groupRows as $group) {
            $layoutIds[] = $group['fieldLayoutId'];
        }

        $fieldLayouts = $this->_generateFieldLayoutArray($layoutIds);

        foreach ($groupRows as $group) {
            if (!empty($group['structure'])) {
                $group['structure'] = [
                    'uid' => $group['structure'],
                    'maxLevels' => (int)$group['structureMaxLevels'] ?: null,
                ];
            } else {
                unset($group['structure']);
            }

            if (isset($fieldLayouts[$group['fieldLayoutId']])) {
                $layoutUid = $fieldLayouts[$group['fieldLayoutId']]['uid'];
                unset($fieldLayouts[$group['fieldLayoutId']]['uid']);
                $group['fieldLayouts'] = [$layoutUid => $fieldLayouts[$group['fieldLayoutId']]];
            }

            $uid = $group['uid'];
            unset($group['structureMaxLevels'], $group['uid'], $group['fieldLayoutId']);

            $groupData[$uid] = $group;
            $groupData[$uid]['siteSettings'] = [];
        }

        $groupSiteRows = (new Query())
            ->select([
                'groups_sites.hasUrls',
                'groups_sites.uriFormat',
                'groups_sites.template',
                'sites.uid AS siteUid',
                'groups.uid AS groupUid',
            ])
            ->from(['groups_sites' => Table::CATEGORYGROUPS_SITES])
            ->innerJoin(['sites' => Table::SITES], '[[sites.id]] = [[groups_sites.siteId]]')
            ->innerJoin(['groups' => Table::CATEGORYGROUPS], '[[groups.id]] = [[groups_sites.groupId]]')
            ->where(['groups.dateDeleted' => null])
            ->andWhere(['sites.dateDeleted' => null])
            ->all();

        foreach ($groupSiteRows as $groupSiteRow) {
            $groupUid = $groupSiteRow['groupUid'];
            $siteUid = $groupSiteRow['siteUid'];
            unset($groupSiteRow['siteUid'], $groupSiteRow['groupUid']);

            $groupSiteRow['hasUrls'] = (bool)$groupSiteRow['hasUrls'];

            $groupData[$groupUid]['siteSettings'][$siteUid] = $groupSiteRow;
        }

        return $groupData;
    }

    /**
     * Return tag group data config array.
     *
     * @return array
     */
    private function _getTagGroupData(): array
    {
        $groupRows = (new Query())
            ->select([
                'groups.name',
                'groups.handle',
                'groups.uid',
                'groups.fieldLayoutId',
            ])
            ->from(['groups' => Table::TAGGROUPS])
            ->where(['groups.dateDeleted' => null])
            ->all();

        $groupData = [];
        $layoutIds = [];

        foreach ($groupRows as $group) {
            $layoutIds[] = $group['fieldLayoutId'];
        }

        $fieldLayouts = $this->_generateFieldLayoutArray($layoutIds);

        foreach ($groupRows as $group) {
            if (isset($fieldLayouts[$group['fieldLayoutId']])) {
                $layoutUid = $fieldLayouts[$group['fieldLayoutId']]['uid'];
                unset($fieldLayouts[$group['fieldLayoutId']]['uid']);
                $group['fieldLayouts'] = [$layoutUid => $fieldLayouts[$group['fieldLayoutId']]];
            }

            $uid = $group['uid'];
            unset($group['uid'], $group['fieldLayoutId']);

            $groupData[$uid] = $group;
        }

        return $groupData;
    }

    /**
     * Return global set data config array.
     *
     * @return array
     */
    private function _getGlobalSetData(): array
    {
        $setRows = (new Query())
            ->select([
                'sets.name',
                'sets.handle',
                'sets.uid',
                'sets.fieldLayoutId',
            ])
            ->from(['sets' => Table::GLOBALSETS])
            ->all();

        $setData = [];
        $layoutIds = [];

        foreach ($setRows as $setRow) {
            $layoutIds[] = $setRow['fieldLayoutId'];
        }

        $fieldLayouts = $this->_generateFieldLayoutArray($layoutIds);

        foreach ($setRows as $setRow) {
            if (isset($fieldLayouts[$setRow['fieldLayoutId']])) {
                $layoutUid = $fieldLayouts[$setRow['fieldLayoutId']]['uid'];
                unset($fieldLayouts[$setRow['fieldLayoutId']]['uid']);
                $setRow['fieldLayouts'] = [$layoutUid => $fieldLayouts[$setRow['fieldLayoutId']]];
            }

            $uid = $setRow['uid'];
            unset($setRow['uid'], $setRow['fieldLayoutId']);

            $setData[$uid] = $setRow;
        }

        return $setData;
    }

    /**
     * Return plugin data config array
     *
     * @return array
     */
    private function _getPluginData(): array
    {
        $plugins = (new Query())
            ->select([
                'handle',
                'schemaVersion',
            ])
            ->from([Table::PLUGINS])
            ->all();

        $pluginData = [];

        foreach ($plugins as $plugin) {
            $pluginData[$plugin['handle']] = [
                'schemaVersion' => $plugin['schemaVersion'],
            ];
        }

        return $pluginData;
    }

    /**
     * Return asset transform config array
     *
     * @return array
     */
    private function _getTransformData(): array
    {
        $transformRows = (new Query())
            ->select([
                'name',
                'handle',
                'mode',
                'position',
                'width',
                'height',
                'format',
                'quality',
                'interlace',
                'uid',
            ])
            ->from([Table::ASSETTRANSFORMS])
            ->indexBy('uid')
            ->all();

        foreach ($transformRows as &$row) {
            unset($row['uid']);
            $row['width'] = (int)$row['width'] ?: null;
            $row['height'] = (int)$row['height'] ?: null;
            $row['quality'] = (int)$row['quality'] ?: null;
        }

        return $transformRows;
    }

    /**
     * Return GraphQL config array
     *
     * @return array
     */
    private function _getGqlData(): array
    {
        $scopeRows = (new Query())
            ->select([
                'name',
                'scope',
                'isPublic',
                'uid',
            ])
            ->from([Table::GQLSCHEMAS])
            ->indexBy('uid')
            ->all();

        foreach ($scopeRows as &$row) {
            unset($row['uid']);
            $row['isPublic'] = (bool)$row['isPublic'];
            $row['scope'] = Json::decodeIfJson($row['scope']);
        }

        $output = [
            'schemas' => $scopeRows,
            'publicToken' => [
                'enabled' => false,
                'expiryDate' => null,
            ]
        ];

        $publicToken = (new Query())
            ->select([
                'enabled',
                'expiryDate',
            ])
            ->from([Table::GQLTOKENS])
            ->where(['accessToken' => GqlToken::PUBLIC_TOKEN])
            ->one();

        if ($publicToken) {
            $output['publicToken']['expiryDate'] = $publicToken['expiryDate'] ? DateTimeHelper::toDateTime($publicToken['expiryDate'])->getTimestamp() : null;
            $output['publicToken']['enabled'] = (bool)$publicToken['enabled'];
        }

        return $output;
    }

    /**
     * Generate field layout config data for a list of array ids
     *
     * @param int[] $layoutIds
     *
     * @return array
     */
    private function _generateFieldLayoutArray(array $layoutIds): array
    {
        // Get all the UIDs
        $fieldLayoutUids = (new Query())
            ->select(['id', 'uid'])
            ->from([Table::FIELDLAYOUTS])
            ->where(['id' => $layoutIds])
            ->pairs();

        $fieldLayouts = [];
        foreach ($fieldLayoutUids as $id => $uid) {
            $fieldLayouts[$id] = [
                'uid' => $uid,
                'tabs' => [],
            ];
        }

        // Get the tabs and fields
        $fieldRows = (new Query())
            ->select([
                'fields.handle',
                'fields.uid AS fieldUid',
                'layoutFields.fieldId',
                'layoutFields.required',
                'layoutFields.sortOrder AS fieldOrder',
                'tabs.id AS tabId',
                'tabs.name as tabName',
                'tabs.sortOrder AS tabOrder',
                'tabs.uid AS tabUid',
                'layouts.id AS layoutId',
            ])
            ->from(['layoutFields' => Table::FIELDLAYOUTFIELDS])
            ->innerJoin(['tabs' => Table::FIELDLAYOUTTABS], '[[tabs.id]] = [[layoutFields.tabId]]')
            ->innerJoin(['layouts' => Table::FIELDLAYOUTS], '[[layouts.id]] = [[layoutFields.layoutId]]')
            ->innerJoin(['fields' => Table::FIELDS], '[[fields.id]] = [[layoutFields.fieldId]]')
            ->where(['layouts.id' => $layoutIds])
            ->andWhere(['layouts.dateDeleted' => null])
            ->orderBy(['tabs.sortOrder' => SORT_ASC, 'layoutFields.sortOrder' => SORT_ASC])
            ->all();

        foreach ($fieldRows as $fieldRow) {
            $layout = &$fieldLayouts[$fieldRow['layoutId']];

            if (empty($layout['tabs'][$fieldRow['tabUid']])) {
                $layout['tabs'][$fieldRow['tabUid']] =
                    [
                        'name' => $fieldRow['tabName'],
                        'sortOrder' => (int)$fieldRow['tabOrder'],
                    ];
            }

            $tab = &$layout['tabs'][$fieldRow['tabUid']];

            $field['required'] = (bool)$fieldRow['required'];
            $field['sortOrder'] = (int)$fieldRow['fieldOrder'];

            $tab['fields'][$fieldRow['fieldUid']] = $field;
        }

        // Get rid of UIDs
        foreach ($fieldLayouts as &$fieldLayout) {
            $fieldLayout['tabs'] = array_values($fieldLayout['tabs']);
        }

        return $fieldLayouts;
    }

    /**
     * Returns a project config compatible value encoded for storage.
     *
     * @param $value
     * @return string
     */
    protected function encodeValueAsString($value): string
    {
        return Json::encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }
}
