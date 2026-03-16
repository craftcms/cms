<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig;

use Craft;
use craft\helpers\App;
use craft\helpers\DateTimeHelper;
use craft\helpers\FileHelper;
use craft\services\ElementSources;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\ImageTransforms;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\ProjectConfig\Data\ProjectConfigData;
use CraftCms\Cms\ProjectConfig\Data\ReadOnlyProjectConfigData;
use CraftCms\Cms\ProjectConfig\Events\ChangesApplied;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\Events\ItemAdded;
use CraftCms\Cms\ProjectConfig\Events\ItemRemoved;
use CraftCms\Cms\ProjectConfig\Events\ItemUpdated;
use CraftCms\Cms\ProjectConfig\Events\RebuildConfig;
use CraftCms\Cms\ProjectConfig\Events\YamlFilesWritten;
use CraftCms\Cms\ProjectConfig\Exceptions\BusyResourceException;
use CraftCms\Cms\ProjectConfig\Exceptions\ReadonlyException;
use CraftCms\Cms\ProjectConfig\Exceptions\StaleResourceException;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Shared\Exceptions\OperationAbortedException;
use CraftCms\Cms\Shared\Models\Info;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Site\Data\SiteGroup;
use CraftCms\Cms\Support\Facades\Conditions;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Filesystems;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\SiteGroups;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use CraftCms\DependencyAwareCache\Dependency\CallbackDependency;
use CraftCms\DependencyAwareCache\Facades\DependencyCache;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;
use Throwable;
use yii\base\Application;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\web\ServerErrorHttpException;

use function Illuminate\Filesystem\join_paths;

#[Singleton]
class ProjectConfig
{
    /**
     * The cache key that is used to store the modified time of the project config files, at the time they were last applied.
     */
    public const string CACHE_KEY = 'projectConfig:files';

    /**
     * The cache key that is used to store the loaded project config data.
     */
    public const string STORED_CACHE_KEY = 'projectConfig:internal';

    /**
     * The cache key that is used to store whether there were any issues writing the project config files out.
     */
    public const string FILE_ISSUES_CACHE_KEY = 'projectConfig:fileIssues';

    /**
     * The cache key that is used to store the current project config diff
     */
    public const string DIFF_CACHE_KEY = 'projectConfig:diff';

    /**
     * The duration that project config caches should be cached.
     */
    public const int CACHE_DURATION = 60 * 60 * 24 * 365; // 1 year

    /**
     * @var string Filename for base config file
     */
    public const string CONFIG_FILENAME = 'project.yaml';

    /**
     * Filename for base config delta files
     */
    public const string CONFIG_DELTA_FILENAME = 'delta.yaml';

    /**
     * The array key to use for signaling ordered-to-associative array conversion.
     */
    public const string ASSOC_KEY = '__assoc__';

    /**
     * @see _acquireLock()
     * @see _releaseLock()
     */
    public const string MUTEX_NAME = 'project-config';

    public const string PATH_ADDRESSES = 'addresses';

    public const string PATH_ADDRESS_FIELD_LAYOUTS = self::PATH_ADDRESSES.'.'.'fieldLayouts';

    public const string PATH_DATE_MODIFIED = 'dateModified';

    public const string PATH_ELEMENT_SOURCES = 'elementSources';

    public const string PATH_ELEMENT_SOURCE_PAGES = 'elementSourcesPages';

    public const string PATH_ENTRY_TYPES = 'entryTypes';

    public const string PATH_FIELDS = 'fields';

    public const string PATH_FS = 'fs';

    public const string PATH_GRAPHQL = 'graphql';

    public const string PATH_GRAPHQL_PUBLIC_TOKEN = self::PATH_GRAPHQL.'.'.'publicToken';

    public const string PATH_GRAPHQL_SCHEMAS = self::PATH_GRAPHQL.'.'.'schemas';

    public const string PATH_IMAGE_TRANSFORMS = 'imageTransforms';

    public const string PATH_META = 'meta';

    public const string PATH_META_NAMES = self::PATH_META.'.__names__';

    public const string PATH_PLUGINS = 'plugins';

    public const string PATH_ROUTES = 'routes';

    public const string PATH_SCHEMA_VERSION = self::PATH_SYSTEM.'.schemaVersion';

    public const string PATH_SECTIONS = 'sections';

    public const string PATH_SITES = 'sites';

    public const string PATH_SITE_GROUPS = 'siteGroups';

    public const string PATH_SYSTEM = 'system';

    public const string PATH_USERS = 'users';

    public const string PATH_USER_FIELD_LAYOUTS = self::PATH_USERS.'.'.'fieldLayouts';

    public const string PATH_USER_GROUPS = self::PATH_USERS.'.groups';

    public const string PATH_VOLUMES = 'volumes';

    // Regexp patterns
    // -------------------------------------------------------------------------

    /**
     * Regexp pattern to determine a string that could be used as an UID.
     */
    public const string UID_PATTERN = '[a-zA-Z0-9_-]+';

    /**
     * @var bool Whether project config changes should be written to YAML files automatically.
     *
     * If set to `false`, you can manually write out project config YAML files using the `project-config/write` command.
     *
     * ::: warning
     * If this is set to `false`, Craft won’t have a strong grasp of whether the YAML files or database contain the most relevant
     * project config data, so there’s a chance that the Project Config utility will be a bit misleading.
     * :::
     *
     * @see flush()
     */
    public bool $writeYamlAutomatically = true;

    /**
     * @var string The folder name to save the project config files in, within the `config/` folder.
     */
    public string $folderName = 'project';

    /**
     * @var int The maximum number of project.yaml deltas to store in storage/config-deltas/
     */
    public int $maxDeltas = 50;

    /**
     * @var int The maximum number of times deferred events can be re-deferred before we give up on them
     *
     * @see defer()
     * @see _applyChanges()
     */
    public int $maxDefers = 500;

    /**
     * @var bool Whether the project config is read-only.
     */
    public bool $readOnly = false;

    /**
     * @var bool Whether events generated by config changes should be muted.
     */
    public bool $muteEvents = false;

    /**
     * @var bool Whether project config should force updates on entries that aren't new or being removed.
     */
    public bool $forceUpdate = false;

    /**
     * @var array A list of all external files.
     */
    private array $_configFileList = [];

    /**
     * @var int|null The project config cache duration. If null, the <config5:cacheDuration> config setting will be used.
     */
    public ?int $cacheDuration = null;

    /**
     * @var bool Whether to write out updated YAML changes at the end of the request
     */
    private bool $_updateYaml = false;

    /**
     * @var bool Whether we’re listening for the request end, to update the config parse time caches.
     *
     * @see updateParsedConfigTimes()
     */
    public private(set) bool $waitingToUpdateParsedConfigTimes = false;

    /**
     * @var bool Whether external project config changes are currently being applied.
     */
    public private(set) bool $isApplyingExternalChanges = false;

    /**
     * @var bool Whether the config's dateModified timestamp has been updated by this request.
     */
    private bool $_timestampUpdated = false;

    /**
     * @var array Deferred config sync events
     *
     * @see defer()
     * @see _applyChanges()
     */
    private array $_deferredEvents = [];

    /**
     * A running list of all the changes applied during this request
     */
    private array $_appliedChanges = [];

    /**
     * @var ReadOnlyProjectConfigData|null Config as defined in the external config.
     */
    private ?ReadOnlyProjectConfigData $_externalConfig = null;

    /**
     * @var ReadOnlyProjectConfigData|null Current config as stored in database.
     */
    private ?ReadOnlyProjectConfigData $_internalConfig = null;

    /**
     * @var ProjectConfigData|null The currently working config - it consists of the current config plus any changes
     *                             applied during this request.
     */
    private ?ProjectConfigData $_currentWorkingConfig = null;

    /**
     * @var array[] Config change handlers
     *
     * @see registerChangeEventHandler()
     * @see handleChangeEvent()
     */
    private array $_changeEventHandlers = [];

    /**
     * @var array[] The specificity of change event handlers.
     *
     * @see registerChangeEventHandler()
     * @see handleChangeEvent()
     * @see _sortChangeEventHandlers()
     */
    private array $_changeEventHandlerSpecificity = [];

    /**
     * @var array[] The registration order of change event handlers.
     *
     * @see registerChangeEventHandler()
     * @see handleChangeEvent()
     * @see _sortChangeEventHandlers()
     */
    private array $_changeEventHandlerRegistrationOrder = [];

    /**
     * @var bool[] Whether the change event handlers have been sorted.
     *
     * @see registerChangeEventHandler()
     * @see handleChangeEvent()
     * @see _sortChangeEventHandlers()
     */
    private array $_sortedChangeEventHandlers = [];

    /**
     * @var bool Whether a mutex lock was acquired for this request
     *
     * @see _acquireLock()
     * @see _releaseLock()
     */
    private bool $_locked = false;

    public function __construct(GeneralConfig $generalConfig)
    {
        Event::listen(ItemAdded::class, $this->handleChangeEvent(...));
        Event::listen(ItemUpdated::class, $this->handleChangeEvent(...));
        Event::listen(ItemRemoved::class, $this->handleChangeEvent(...));

        $this->readOnly = Cms::isInstalled() && ! $generalConfig->allowAdminChanges;
        $this->writeYamlAutomatically = ! App::isEphemeral();
    }

    /**
     * Saves the modified project config state and writes out updated YAML files, if needed.
     */
    public function flush(): void
    {
        $this->saveModifiedConfigData();

        if ($this->writeYamlAutomatically) {
            $this->writeYamlFiles();
        }
    }

    /**
     * Resets the internal state.
     *
     * @internal
     */
    public function reset(): void
    {
        $this->_internalConfig = null;
        $this->_externalConfig = null;
        $this->_currentWorkingConfig = null;
        $this->_configFileList = [];
        $this->_updateYaml = false;
        $this->_appliedChanges = [];
        $this->isApplyingExternalChanges = false;
        $this->_timestampUpdated = false;
    }

    /**
     * Returns a config item value by its path.
     *
     * ---
     *
     * ```php
     * $value = Craft::$app->projectConfig->get('foo.bar');
     * ```
     *
     * @param  string|null  $path  The config item path, or `null` if the entire config should be returned
     * @param  bool  $getFromExternalConfig  whether data should be fetched from the working config instead of the loaded config. Defaults to `false`.
     * @return mixed The config item value
     */
    public function get(?string $path = null, bool $getFromExternalConfig = false): mixed
    {
        if ($getFromExternalConfig) {
            $source = $this->getExternalConfig();
        } else {
            $source = $this->getCurrentWorkingConfig();
        }

        if ($path === null) {
            return $source->export();
        }

        return $source->get($path);
    }

    /**
     * Finds all config items that pass a condition, and returns their paths and configs as key/value pairs.
     *
     * @param  bool  $fromExternalConfig  whether to find config items in the external config
     */
    public function find(callable $callback, bool $fromExternalConfig = false): array
    {
        $items = [];

        $this->findInternal($this->get(null, $fromExternalConfig), $callback, null, $items);

        return $items;
    }

    private function findInternal(array $config, callable $callback, ?string $path, array &$items): void
    {
        foreach ($config as $key => $item) {
            if (is_array($item)) {
                $itemPath = sprintf('%s%s', ($path !== null) ? "$path." : '', $key);
                if ($callback($item, $itemPath)) {
                    $items[$itemPath] = $item;
                } else {
                    $this->findInternal($item, $callback, $itemPath, $items);
                }
            }
        }
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
     * @param  string  $path  The config item path
     * @param  mixed  $value  The config item value
     * @param  string|null  $message  A message describing the changes
     * @param  bool  $updateTimestamp  Whether the `dateModified` value should be updated, if it hasn’t been updated yet for this request
     * @param  bool  $force  Whether the update should be processed regardless of whether the value actually changed
     * @return bool Whether the project config was modified
     *
     * @throws ErrorException
     * @throws Exception
     * @throws NotSupportedException if the service is set to read-only mode
     * @throws ServerErrorHttpException
     * @throws InvalidConfigException
     * @throws BusyResourceException if a lock could not be acquired
     * @throws StaleResourceException if the loaded project config is out-of-date
     */
    public function set(
        string $path,
        mixed $value,
        ?string $message = null,
        bool $updateTimestamp = true,
        bool $force = false,
    ): bool {
        if (! $this->_setInternal($path, $value, $message, $updateTimestamp, $force)) {
            return false;
        }

        $this->_saveConfigAfterRequest();

        return true;
    }

    private function _setInternal(
        string $path,
        mixed $value,
        ?string $message = null,
        bool $updateTimestamp = true,
        bool $force = false,
    ): bool {
        if (is_array($value)) {
            $value = ProjectConfigHelper::cleanupConfig($value);
        }

        $workingConfig = $this->getCurrentWorkingConfig();
        $previousValue = $workingConfig->get($path);
        $valueHasChanged = $value !== $previousValue;

        if (! $valueHasChanged && ! $force) {
            return false;
        }

        if ($this->readOnly && $valueHasChanged) {
            // If we're applying yaml changes that are coming in via external config, anyway, bail silently.
            if ($this->isApplyingExternalChanges && $value === $this->getExternalConfig()->get($path)) {
                return true;
            }

            throw new ReadonlyException('Changes to the project config are not possible while in read-only mode.');
        }

        if ($updateTimestamp && ! $this->_timestampUpdated && $valueHasChanged) {
            $this->_timestampUpdated = true;
            $this->_setInternal(self::PATH_DATE_MODIFIED, DateTimeHelper::currentTimeStamp(),
                'Update timestamp for project config', false, false);
        }

        if ($valueHasChanged) {
            $this->_acquireLock();
        }

        $this->getCurrentWorkingConfig()->commitChanges($previousValue, $value, $path, $valueHasChanged, $message,
            true);

        return true;
    }

    /**
     * Removes a config item at the given path.
     *
     * ---
     * ```php
     * Craft::$app->projectConfig->remove('foo.bar');
     * ```
     *
     * @param  string  $path  The config item path
     * @param  string|null  $message  The message describing changes.
     */
    public function remove(string $path, ?string $message = null): void
    {
        $this->set($path, null, $message);
    }

    /**
     * Regenerates the external config based on the loaded project config.
     */
    public function regenerateExternalConfig(): void
    {
        $this->isApplyingExternalChanges = false;

        // Ensure we have the working config
        $this->getCurrentWorkingConfig();

        // And ensure we save it.
        $this->_saveConfigAfterRequest();
        $this->updateParsedConfigTimesAfterRequest();
        $this->saveModifiedConfigData();
        $this->writeYamlFiles(true);
    }

    /**
     * Applies changes in external config to project config.
     *
     * @throws BusyResourceException if a lock could not be acquired
     * @throws StaleResourceException if the loaded project config is out-of-date
     */
    public function applyExternalChanges(): void
    {
        $this->_acquireLock();

        // Disable read/write splitting for the remainder of this request
        DB::connection()->useWriteConnectionWhenReading();

        // Start with a clean slate.
        $this->reset();

        $this->isApplyingExternalChanges = true;
        Cache::forget(self::CACHE_KEY);

        $changes = $this->_getPendingChanges();

        $this->_applyChanges($changes, $this->getCurrentWorkingConfig(), $this->getExternalConfig());
        $anyChangesApplied = (bool) (count($changes['newItems']) + count($changes['removedItems']) + count($changes['changedItems']));

        // Kill the cached config data
        Cache::forget(self::STORED_CACHE_KEY);
        if ($anyChangesApplied) {
            $this->updateConfigVersion();
        }

        $this->_releaseLock();
    }

    public function isApplyingExternalChanges(): bool
    {
        return $this->isApplyingExternalChanges;
    }

    /**
     * Applies given changes to the project config.
     */
    public function applyConfigChanges(array $configData): void
    {
        $this->isApplyingExternalChanges = true;

        $changes = $this->_getPendingChanges($configData);
        $incomingConfig = new ReadOnlyProjectConfigData($configData, $this);

        $this->_applyChanges($changes, $this->getCurrentWorkingConfig(), $incomingConfig);
    }

    /**
     * Returns whether external project config files appear to exist.
     */
    public function getDoesExternalConfigExist(): bool
    {
        return file_exists(Path::projectConfigFile());
    }

    /**
     * Returns whether a given path has pending changes that need to be applied to the loaded project config.
     *
     * @param  string|null  $path  A specific config path that should be checked for pending changes.
     *                             If this is null, then `true` will be returned if there are *any* pending changes in external config.
     * @param  bool  $force  Whether to check for changes even if it doesn’t look like anything has changed since
     *                       the last time [[ignorePendingChanges()]] has been called.
     */
    public function areChangesPending(?string $path = null, bool $force = false): bool
    {
        // If the path is currently being processed, return true
        if ($path !== null && $this->getCurrentWorkingConfig()->getHasPathBeenModified($path)) {
            return true;
        }

        // If the file does not exist, but should, generate it
        if ($this->getHadFileWriteIssues() || ! $this->getDoesExternalConfigExist()) {
            if ($this->writeYamlAutomatically) {
                $this->regenerateExternalConfig();
            } else {
                $this->saveModifiedConfigData();
            }

            return false;
        }

        if (! $force) {
            // If the file modification date hasn't changed, then no need to check the contents
            $cachedModifiedTime = Cache::get(self::CACHE_KEY);
            if (
                $cachedModifiedTime &&
                $cachedModifiedTime === $this->_getConfigFileModifiedTime()
            ) {
                return false;
            }
        }

        if ($path !== null) {
            $oldValue = $this->getInternalConfig()->get($path);
            $newValue = $this->getExternalConfig()->get($path);

            return ProjectConfigHelper::encodeValueAsString($oldValue) !== ProjectConfigHelper::encodeValueAsString($newValue);
        }

        // If the file contents haven't changed, just update the cached file modification date
        if (! $this->_getPendingChanges(null, true)) {
            $this->updateParsedConfigTimes();

            return false;
        }

        // Clear the cached config, just in case it conflicts with what we've got here
        Cache::forget(self::STORED_CACHE_KEY);
        $this->_currentWorkingConfig = null;

        return true;
    }

    /**
     * Processes changes in the project config files for a given config item path.
     *
     * Note that this will only have an effect if external project config changes are currently getting [[getIsApplyingExternalChanges()|applied]].
     *
     * @param  string  $path  The config item path
     * @param  bool  $force  Whether the config change should be processed regardless of previous records,
     *                       or whether external changes are currently being applied
     */
    public function processConfigChanges(string $path, bool $force = false): void
    {
        if ($force || $this->isApplyingExternalChanges) {
            $this->getCurrentWorkingConfig()->commitChanges($this->getInternalConfig()->get($path),
                $this->getExternalConfig()->get($path), $path, false, null, $force);
        }
    }

    /**
     * Updates cached config file modified times after the request ends.
     */
    public function updateParsedConfigTimesAfterRequest(): void
    {
        if ($this->waitingToUpdateParsedConfigTimes) {
            return;
        }

        $this->waitingToUpdateParsedConfigTimes = true;
    }

    /**
     * Updates cached config file modified times immediately.
     */
    public function updateParsedConfigTimes(): bool
    {
        return Cache::put(
            self::CACHE_KEY,
            $this->_getConfigFileModifiedTime(),
            self::CACHE_DURATION,
        );
    }

    /**
     * Saves all the config data that has been modified up to now.
     */
    public function saveModifiedConfigData(): void
    {
        if (empty($this->_appliedChanges)) {
            $this->_releaseLock();

            return;
        }

        $deltaChanges = [];

        DB::transaction(function () use (&$deltaChanges) {
            foreach ($this->_appliedChanges as $changeSet) {
                // Allow modification of the array being looped over.
                $currentSet = $changeSet;

                if (! empty($changeSet['removed'])) {
                    $this->removeInternalConfigValuesByPaths(array_keys($changeSet['removed']));
                }

                if (! empty($changeSet['added'])) {
                    $isMysql = DB::isMysql();
                    $batch = [];
                    $pathsToInsert = [];
                    $additionalCleanupPaths = [];

                    foreach ($currentSet['added'] as $key => $value) {
                        // Prepare for storage
                        $dbValue = ProjectConfigHelper::encodeValueAsString($value);
                        if (! mb_check_encoding($dbValue, 'UTF-8') || ($isMysql && Str::containsMb4($dbValue))) {
                            $dbValue = 'base64:'.base64_encode($dbValue);
                        }
                        $batch[$key] = $dbValue;
                        $pathsToInsert[] = $key;

                        // Delete parent key, as it cannot hold a value AND be an array at the same time
                        $additionalCleanupPaths[ProjectConfigHelper::pathWithoutLastSegment($key) ?? $key] = true;

                        // Prepare for delta
                        if (! empty($currentSet['removed']) && array_key_exists($key, $currentSet['removed'])) {
                            if (is_string($changeSet['removed'][$key])) {
                                $changeSet['removed'][$key] = Str::decdec($changeSet['removed'][$key]);
                            }

                            $changeSet['removed'][$key] = Json::decodeIfJson($changeSet['removed'][$key]);

                            // Ensure types
                            if (is_bool($value)) {
                                $changeSet['removed'][$key] = (bool) $changeSet['removed'][$key];
                            } elseif (is_int($value)) {
                                $changeSet['removed'][$key] = (int) $changeSet['removed'][$key];
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
                    if (! empty($batch)) {
                        $this->removeInternalConfigValuesByPaths($pathsToInsert);
                        $this->removeInternalConfigValuesByPaths(array_keys($additionalCleanupPaths));
                        $this->persistInternalConfigValues($batch);
                    }
                }

                $changeSet = array_filter($changeSet);

                if (! empty($changeSet)) {
                    $deltaChanges[] = $changeSet;
                }
            }

            $this->updateConfigVersion();
            $this->_releaseLock();
        });

        if (! empty($deltaChanges)) {
            $this->storeYamlHistory([
                'dateApplied' => date('Y-m-d H:i:s'),
                'changes' => $deltaChanges,
            ]);
        }
    }

    /**
     * Remove values from internal config by a list of paths.
     */
    private function removeInternalConfigValuesByPaths(array $paths): void
    {
        $chunks = array_chunk($paths, 1000);

        foreach ($chunks as $chunk) {
            DB::table(Table::PROJECTCONFIG)
                ->whereIn('path', $chunk)
                ->delete();
        }
    }

    /**
     * Persist an array of `$path => $value` to the internal config.
     */
    private function persistInternalConfigValues(array $values): void
    {
        DB::table(Table::PROJECTCONFIG)
            ->insert(Collection::make($values)->map(fn ($value, $path) => [
                'path' => $path,
                'value' => $value,
            ])->all());
    }

    /**
     * Returns a summary of all pending config changes.
     */
    public function getPendingChangeSummary(): array
    {
        $pendingChanges = $this->_getPendingChanges();

        $summary = [];

        // Reduce all the small changes to overall item changes.
        foreach ($pendingChanges as $type => $changes) {
            $summary[$type] = [];
            foreach ($changes as $path) {
                $pathParts = explode('.', (string) $path);
                if (count($pathParts) > 1) {
                    $summary[$type][$pathParts[0].'.'.$pathParts[1]] = true;
                }
            }
        }

        return $summary;
    }

    /**
     * Get the list of applied changes
     */
    public function getAppliedChanges(): array
    {
        return $this->_appliedChanges;
    }

    /**
     * Returns whether all schema versions stored in the config are compatible with the actual codebase.
     * The schemas must match exactly to avoid unpredictable behavior that can occur when running migrations
     * and applying project config changes at the same time.
     *
     * @param  array  $issues  Passed by reference and populated with issues on error in
     *                         the following format: `[$pluginName, $existingSchema, $incomingSchema]`
     */
    public function getAreConfigSchemaVersionsCompatible(array &$issues = []): bool
    {
        $incomingSchema = (string) $this->getExternalConfig()->get(self::PATH_SCHEMA_VERSION);
        $existingSchema = Cms::SCHEMA_VERSION;

        // Compare existing Craft schema version with the one that is being applied.
        if (! version_compare($existingSchema, $incomingSchema, '=')) {
            $issues[] = [
                'cause' => 'Craft CMS',
                'existing' => $existingSchema,
                'incoming' => $incomingSchema,
            ];
        }

        $plugins = app(Plugins::class)->getAllPlugins();

        foreach ($plugins as $plugin) {
            $incomingSchema = (string) $this->getExternalConfig()->get(self::PATH_PLUGINS.'.'.$plugin->handle.'.schemaVersion');
            $existingSchema = $plugin->schemaVersion;

            // Compare existing plugin schema version with the one that is being applied.
            if ($incomingSchema && ! version_compare($existingSchema, $incomingSchema, '=')) {
                $issues[] = [
                    'cause' => $plugin->name,
                    'existing' => $existingSchema,
                    'incoming' => $incomingSchema,
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
     *     $id = \Illuminate\Support\Facades\DB::table(\CraftCms\Cms\Db\'{{%tablename}}')->idByUid($uid);
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
     * @param  string  $path  The config path pattern. Can contain `{uri}` tokens, which will be passed to the handler.
     * @param  callable  $handler  The handler method.
     * @param  mixed  $data  The data to be passed to the event handler when the event is triggered.
     *                       When the event handler is invoked, this data can be accessed via [[ConfigEvent::data]].
     * @return static self reference
     */
    public function onAdd(string $path, callable $handler, mixed $data = null): self
    {
        $this->registerChangeEventHandler(ItemAdded::class, $path, $handler, $data);

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
     * @param  string  $path  The config path pattern. Can contain `{uri}` tokens, which will be passed to the handler.
     * @param  callable  $handler  The handler method.
     * @param  mixed  $data  The data to be passed to the event handler when the event is triggered.
     *                       When the event handler is invoked, this data can be accessed via [[ConfigEvent::data]].
     * @return static self reference
     */
    public function onUpdate(string $path, callable $handler, mixed $data = null): self
    {
        $this->registerChangeEventHandler(ItemUpdated::class, $path, $handler, $data);

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
     * @param  string  $path  The config path pattern. Can contain `{uri}` tokens, which will be passed to the handler.
     * @param  callable  $handler  The handler method.
     * @param  mixed  $data  The data to be passed to the event handler when the event is triggered.
     *                       When the event handler is invoked, this data can be accessed via [[ConfigEvent::data]].
     * @return static self reference
     */
    public function onRemove(string $path, callable $handler, mixed $data = null): self
    {
        $this->registerChangeEventHandler(ItemRemoved::class, $path, $handler, $data);

        return $this;
    }

    /**
     * Defers an event until all other project config changes have been processed.
     */
    public function defer(ConfigEvent $event, callable $handler): void
    {
        Log::info('Deferring event handler for '.$event->path, [__METHOD__]);

        $this->_deferredEvents[] = [$event, $event->tokenMatches, $handler];
    }

    /**
     * Registers a config change event listener, for a specific config path pattern.
     *
     * @param  string  $event  The event name
     * @param  string  $path  The config path pattern. Can contain `{uid}` tokens, which will be passed to the handler.
     * @param  callable  $handler  The handler method.
     * @param  mixed  $data  The data to be passed to the event handler when the event is triggered.
     *                       When the event handler is invoked, this data can be accessed via [[ConfigEvent::data]].
     */
    public function registerChangeEventHandler(string $event, string $path, callable $handler, mixed $data = null): void
    {
        $specificity = substr_count($path, '.');
        $pattern = '/^(?P<path>'.preg_quote($path, '/').')(?P<extra>\..+)?$/';
        $pattern = str_replace('\\{uid\\}', '('.self::UID_PATTERN.')', $pattern);

        $this->_changeEventHandlers[$event][] = [$pattern, $handler, $data];
        $this->_changeEventHandlerSpecificity[$event][] = $specificity;
        $this->_changeEventHandlerRegistrationOrder[$event][] = count($this->_changeEventHandlers[$event]);
        unset($this->_sortedChangeEventHandlers[$event]);
    }

    /**
     * Handles a config change event.
     */
    public function handleChangeEvent(ConfigEvent $event): void
    {
        if (empty($this->_changeEventHandlers[$event::class])) {
            return;
        }

        // Make sure the event handlers are sorted from least-to-most specific
        $this->_sortChangeEventHandlers($event::class);

        foreach ($this->_changeEventHandlers[$event::class] as [$pattern, $handler, $data]) {
            if (! preg_match($pattern, $event->path, $matches)) {
                continue;
            }

            // Is this a nested path?
            if (isset($matches['extra'])) {
                $path = $matches['path'];
                $incomingConfig = $this->isApplyingExternalChanges ? $this->getExternalConfig() : $this->getCurrentWorkingConfig();

                $oldValue = $this->getInternalConfig()->get($path);

                // For containing paths we need to do the following things:
                // 1) get the previous value at the containing path, which will be stale
                // 2) get the extra path component from matches array
                // 3) grab the actual new data from the event and merge it over the stale data
                $newValue = $incomingConfig->get($path);
                $extraPath = Str::chopStart($matches['extra'], '.');
                $newNestedValue = $event->newValue;
                if (is_array($newValue)) {
                    ProjectConfigHelper::traverseDataArray($newValue, $extraPath, $newNestedValue);
                }

                $this->getCurrentWorkingConfig()->commitChanges($oldValue, $newValue, $path);

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

    /**
     * Ensures that the config change event handlers are sorted by least-to-most specific.
     */
    private function _sortChangeEventHandlers(string $event): void
    {
        if (isset($this->_sortedChangeEventHandlers[$event])) {
            return;
        }

        array_multisort(
            $this->_changeEventHandlerSpecificity[$event], SORT_ASC, SORT_NUMERIC,
            $this->_changeEventHandlerRegistrationOrder[$event], SORT_ASC, SORT_NUMERIC,
            $this->_changeEventHandlers[$event],
        );

        $this->_sortedChangeEventHandlers[$event] = true;
    }

    /**
     * Rebuilds the project config from the current state in the database.
     *
     * @throws BusyResourceException if a lock could not be acquired
     * @throws StaleResourceException if the loaded project config is out-of-date
     * @throws Throwable if reasons
     */
    public function rebuild(): void
    {
        $this->_acquireLock();
        $this->reset();

        $this->muteEvents = true;
        $readOnly = $this->readOnly;
        $this->readOnly = false;

        $config = $this->getInternalConfig()->export();

        // don't touch `meta`
        unset($config[self::PATH_META]);

        $config[self::PATH_ADDRESSES] = $this->_getAddressesData();
        $config[self::PATH_DATE_MODIFIED] = DateTimeHelper::currentTimeStamp();
        $config[self::PATH_ELEMENT_SOURCES] = $this->_getElementSourceData($config[self::PATH_ELEMENT_SOURCES] ?? []);
        $config[self::PATH_ENTRY_TYPES] = $this->_getEntryTypeData();
        $config[self::PATH_FIELDS] = $this->_getFieldData();
        $config[self::PATH_FS] = $this->_getFsData();
        $config[self::PATH_GRAPHQL] = $this->_getGqlData();
        $config[self::PATH_IMAGE_TRANSFORMS] = $this->_getTransformData();
        $config[self::PATH_PLUGINS] = $this->_getPluginData($config[self::PATH_PLUGINS] ?? []);
        $config[self::PATH_SECTIONS] = $this->_getSectionData();
        $config[self::PATH_SITES] = $this->_getSiteData();
        $config[self::PATH_SITE_GROUPS] = $this->_getSiteGroupData();
        $config[self::PATH_SYSTEM] = $this->_systemConfig($config[self::PATH_SYSTEM] ?? []);
        $config[self::PATH_USERS] = $this->_getUserData($config[self::PATH_USERS] ?? []);
        $config[self::PATH_VOLUMES] = $this->_getVolumeData();

        // Fire a 'rebuild' event
        event($event = new RebuildConfig($config));

        // Reset the component name map
        $this->_setInternal(self::PATH_META_NAMES, [], updateTimestamp: false, force: true);

        // Process the changes
        foreach ($event->config as $path => $value) {
            $this->_setInternal($path, $value, 'Project config rebuild', updateTimestamp: false, force: true);
        }

        // Make sure we save it all.
        $this->_saveConfigAfterRequest();
        $this->updateConfigVersion();

        if ($this->writeYamlAutomatically) {
            $this->writeYamlFiles();
        }

        // And now ensure that Project Config doesn't attempt to export the config again
        $this->_updateYaml = false;

        $this->readOnly = $readOnly;
        $this->muteEvents = false;
    }

    /**
     * Applies changes from a configuration array.
     *
     * @param  array  $changes  array nested array with keys `removedItems`, `changedItems` and `newItems`
     * @param  ReadOnlyProjectConfigData  $existingConfig  The config data repository that holds the current data
     * @param  ReadOnlyProjectConfigData  $incomingConfig  The config data repository that holds the incoming data
     *
     * @throws OperationAbortedException
     */
    private function _applyChanges(
        array $changes,
        ReadOnlyProjectConfigData $existingConfig,
        ReadOnlyProjectConfigData $incomingConfig,
    ): void {
        Log::info('Looking for pending changes', [__METHOD__]);

        $processChanges = function ($path, $triggerUpdate = false) use ($existingConfig, $incomingConfig) {
            $oldValue = $existingConfig->get($path);
            $newValue = $incomingConfig->get($path);
            $this->getCurrentWorkingConfig()->commitChanges($oldValue, $newValue, $path, $triggerUpdate, null, true);
        };

        // If we're parsing all the changes, we better work the actual config map.
        if (! empty($changes['removedItems'])) {
            Log::info('Parsing '.count($changes['removedItems']).' removed configuration items', [__METHOD__]);
            foreach ($changes['removedItems'] as $itemPath) {
                $processChanges($itemPath);
            }
        }

        if (! empty($changes['changedItems'])) {
            Log::info('Parsing '.count($changes['changedItems']).' changed configuration items', [__METHOD__]);
            foreach ($changes['changedItems'] as $itemPath) {
                $processChanges($itemPath);
            }
        }

        if (! empty($changes['newItems'])) {
            Log::info('Parsing '.count($changes['newItems']).' new configuration items', [__METHOD__]);
            // It's possible that a key has both a new value and a changed value.
            // Make sure we process paths that might have been added but not processed yet.
            foreach ($changes['newItems'] as $itemPath) {
                $processChanges($itemPath, true);
            }
        }

        $defers = -count($this->_deferredEvents);
        while (! empty($this->_deferredEvents)) {
            if ($defers > $this->maxDefers) {
                $paths = [];

                // Grab a list of all deferred event paths
                foreach ($this->_deferredEvents as [$deferredEvent]) {
                    // Save us the trouble of filtering out duplicates later
                    $paths[$deferredEvent->path] = true;
                }

                $message = "The following config paths could not be processed successfully:\n".implode("\n",
                    array_keys($paths));
                throw new OperationAbortedException($message);
            }

            /** @var ConfigEvent $event */
            /** @var string[]|null $tokenMatches */
            /** @var callable $handler */
            [$event, $tokenMatches, $handler] = array_shift($this->_deferredEvents);
            Log::info('Re-triggering deferred event for '.$event->path, [__METHOD__]);
            $event->tokenMatches = $tokenMatches;
            $handler($event);
            $event->tokenMatches = null;
            $defers++;
        }

        Log::info('Finalizing configuration parsing', [__METHOD__]);

        event(new ChangesApplied);

        $this->updateParsedConfigTimesAfterRequest();
        $this->isApplyingExternalChanges = false;
    }

    /**
     * Retrieve a config file tree with modified times based on the main configuration file.
     */
    private function _getConfigFileModifiedTime(): int
    {
        $path = Path::projectConfigFile();

        if (! file_exists($path)) {
            return 0;
        }

        return filemtime($path);
    }

    /**
     * Load the config stored in the external storage.
     */
    private function _loadExternalConfig(): ReadOnlyProjectConfigData
    {
        // If the external config does not exist, just use the loaded config
        if ($this->getHadFileWriteIssues() || ! $this->getDoesExternalConfigExist()) {
            return $this->getCurrentWorkingConfig();
        }

        $fileList = $this->_getConfigFileList();
        $generatedConfig = [];
        $projectConfigPathLength = strlen((string) Path::projectConfig(create: false));

        foreach ($fileList as $filePath) {
            $yamlConfig = Yaml::parse(file_get_contents($filePath));
            $subPath = substr((string) $filePath, $projectConfigPathLength + 1);

            if (Str::substrCount($subPath, DIRECTORY_SEPARATOR) > 0) {
                $configPath = explode(DIRECTORY_SEPARATOR, $subPath);
                $filename = pathinfo(array_pop($configPath), PATHINFO_FILENAME);
                $insertionPoint = &$generatedConfig;

                foreach ($configPath as $pathSegment) {
                    if (! isset($insertionPoint[$pathSegment])) {
                        $insertionPoint[$pathSegment] = [];
                    }

                    $insertionPoint = &$insertionPoint[$pathSegment];
                }

                /** @var string $pathSegment */
                /** @phpstan-ignore-next-line */
                if ($pathSegment === $filename) {
                    $insertionPoint = array_merge($insertionPoint, $yamlConfig);
                } else {
                    // Is this in the <handle>--<uid> format?
                    if (preg_match('/^\w+--('.Str::uuidPattern().')$/', $filename, $match)) {
                        // Ignore the handle
                        $filename = $match[1];
                    }
                    $insertionPoint[$filename] = $yamlConfig;
                }
            } else {
                $generatedConfig = array_merge($generatedConfig, $yamlConfig);
            }
        }

        return new ReadOnlyProjectConfigData($generatedConfig, $this);
    }

    /**
     * Return a nested array for pending config changes
     *
     * @param  array|null  $configData  config data to use. If null, the config is fetched from the project config files.
     * @param  bool  $existsOnly  whether to just return `true` or `false` depending on whether any changes are found.
     */
    private function _getPendingChanges(?array $configData = null, bool $existsOnly = false): bool|array
    {
        $newItems = [];
        $changedItems = [];

        $currentConfig = $this->getCurrentWorkingConfig()->export();

        if ($configData === null) {
            $configData = $this->getExternalConfig()->export();
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
            $immediateParent = ProjectConfigHelper::pathWithoutLastSegment($key) ?? $key;

            if (! array_key_exists($key, $flatCurrent)) {
                if ($existsOnly) {
                    return true;
                }
                $newItems[] = $immediateParent;
            } elseif ($this->forceUpdate || $flatCurrent[$key] !== $value) {
                if ($existsOnly) {
                    return true;
                }
                $changedItems[] = $immediateParent;
            }

            unset($flatCurrent[$key]);
        }

        if ($existsOnly) {
            return ! empty($flatCurrent);
        }

        $removedItems = array_keys($flatCurrent);

        foreach ($removedItems as &$removedItem) {
            // Drop the last part of path
            $removedItem = ProjectConfigHelper::pathWithoutLastSegment($removedItem) ?? $removedItem;
        }

        unset($removedItem);

        // Sort by number of dots to ensure deepest paths listed first
        $sorter = function ($a, $b) {
            $aDepth = substr_count($a, '.');
            $bDepth = substr_count($b, '.');

            return $bDepth <=> $aDepth;
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
     * Figure out the entire list of yaml config files
     */
    private function _getConfigFileList(): array
    {
        if (! empty($this->_configFileList)) {
            return $this->_configFileList;
        }

        return $this->_configFileList = $this->_findConfigFiles();
    }

    /**
     * Finds all of the `.yaml` files in the `config/project/` folder.
     *
     *
     * @return string[]
     */
    private function _findConfigFiles(?string $path = null): array
    {
        if ($path === null) {
            $path = Path::projectConfig(create: false);
        }
        if (! is_dir($path)) {
            return [];
        }

        return FileHelper::findFiles($path, [
            'only' => ['*.yaml'],
            'caseSensitive' => false,
        ]);
    }

    /**
     * Save configuration data after the request.
     */
    private function _saveConfigAfterRequest(): void
    {
        $this->_updateYaml = true;

        // @todo: Remove when all legacy tests are ported
        // Are we too late for EVENT_AFTER_REQUEST?
        if (Craft::$app->state >= Application::STATE_AFTER_REQUEST) {
            $this->flush();
        }
    }

    /**
     * Store yaml history
     *
     * @param  array  $configData  config data to be saved as history
     *
     * @throws Exception
     */
    private function storeYamlHistory(array $configData): void
    {
        $basePath = Path::configDelta(self::CONFIG_DELTA_FILENAME);

        // Go through all of them and move them forward.
        for ($i = $this->maxDeltas; $i > 0; $i--) {
            $thisFile = $basePath.($i == 1 ? '' : '.'.($i - 1));
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
     * Updates the config version used for cache invalidation.
     */
    private function updateConfigVersion(): void
    {
        Info::fetch()->update([
            'configVersion' => Str::random(12),
        ]);
    }

    /**
     * Update the config YAML files with the buffered changes.
     *
     * @param  bool  $force  Whether to write out the YAML even if there aren’t any new changes
     *
     * @throws Exception if something goes wrong
     */
    public function writeYamlFiles(bool $force = false): void
    {
        if (! $this->_updateYaml && ! $force) {
            return;
        }

        $config = $this->getCurrentWorkingConfig();

        try {
            $basePath = Path::projectConfig();

            // Delete everything except hidden files/folders
            FileHelper::clearDirectory($basePath, [
                'except' => ['.*', '.*/'],
            ]);

            $projectConfigNames = $config->get(self::PATH_META_NAMES);

            $uids = [];
            $replacements = [];

            if (! empty($projectConfigNames)) {
                foreach ($projectConfigNames as $uid => $name) {
                    $uids[] = '/^(.*'.preg_quote((string) $uid).'.*)$/mi';
                    $replacements[] = '$1 # '.$name;
                }
            }

            $splitConfig = ProjectConfigHelper::splitConfigIntoComponents($config->export());
            foreach ($splitConfig as $relativeFile => $configData) {
                $configData = ProjectConfigHelper::cleanupConfig($configData);
                ksort($configData);
                $filePath = join_paths($basePath, $relativeFile);
                $yamlContent = Yaml::dump($configData, 20, 2);
                if (! empty($uids)) {
                    $yamlContent = preg_replace($uids, $replacements, $yamlContent);
                }
                FileHelper::writeToFile($filePath, $yamlContent);
            }
        } catch (Throwable $e) {
            Cache::put(self::FILE_ISSUES_CACHE_KEY, true, self::CACHE_DURATION);
            if (isset($basePath)) {
                // Try to delete everything (again?) so Craft doesn't apply half-baked project config data
                try {
                    FileHelper::clearDirectory($basePath, [
                        'except' => ['.*', '.*/'],
                    ]);
                } catch (Throwable) {
                    // oh well
                }
            }
            throw new Exception('Unable to write new project config files', 0, $e);
        }

        Cache::forget(self::FILE_ISSUES_CACHE_KEY);

        // Let plugins know about it
        event(new YamlFilesWritten);

        $this->_updateYaml = false;
    }

    /**
     * Sets a UUID/name mapping on the working config.
     */
    public function setNameMapping(string $uid, string $name): void
    {
        $this->setNameMappingInternal($uid, $name);
    }

    /**
     * Removes a UUID/name mapping on the working config.
     */
    public function removeNameMapping(string $uid): void
    {
        $this->setNameMappingInternal($uid, null);
    }

    private function setNameMappingInternal(string $uid, ?string $name): void
    {
        if (! $this->readOnly) {
            // call _setInternal() so we avoid recursive calls to _saveConfigAfterRequest() via set()
            $this->_setInternal(sprintf('%s.%s', self::PATH_META_NAMES, $uid), $name, updateTimestamp: false);
        }
    }

    /**
     * Returns whether we have a record of issues writing out files to the project config folder.
     */
    public function getHadFileWriteIssues(): bool
    {
        return $this->writeYamlAutomatically && Cache::get(self::FILE_ISSUES_CACHE_KEY);
    }

    /**
     * Update Craft's internal config store for a path with the new value. If the value
     * is null, it will be removed instead.
     *
     * @param  string|null  $message  message describing the changes made.
     */
    public function rememberAppliedChanges(
        string $path,
        mixed $oldValue,
        mixed $newValue,
        ?string $message = null,
    ): void {
        $appliedChanges = [];

        $modified = ProjectConfigHelper::encodeValueAsString($oldValue) !== ProjectConfigHelper::encodeValueAsString($newValue);

        if ($newValue !== null && ($oldValue === null || $modified)) {
            if (! is_scalar($newValue)) {
                $flatData = [];
                ProjectConfigHelper::flattenConfigArray($newValue, $path, $flatData);
            } else {
                $flatData = [$path => $newValue];
            }

            $appliedChanges['added'] = $flatData;
        }

        if ($oldValue && ($newValue === null || $modified)) {
            if (! is_scalar($oldValue)) {
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
     * Get the external project config data.
     */
    private function getExternalConfig(): ReadOnlyProjectConfigData
    {
        if ($this->_externalConfig === null) {
            $this->_externalConfig = $this->_loadExternalConfig();
        }

        return $this->_externalConfig;
    }

    /**
     * Get the internal project config data.
     */
    private function getInternalConfig(): ReadOnlyProjectConfigData
    {
        if ($this->_internalConfig === null) {
            $this->_internalConfig = $this->_loadInternalConfig();
        }

        return $this->_internalConfig;
    }

    /**
     * Get the current working project config data.
     */
    private function getCurrentWorkingConfig(): ProjectConfigData
    {
        return $this->_currentWorkingConfig ??= new ProjectConfigData(
            data: $this->getInternalConfig()->export(),
            projectConfig: $this,
        );
    }

    /**
     * Load the config stored in the Db
     */
    private function _loadInternalConfig(): ReadOnlyProjectConfigData
    {
        if (! Cms::isInstalled()) {
            return new ReadOnlyProjectConfigData([], $this);
        }

        if (version_compare(Info::fetch()->schemaVersion, '3.1.1', '<')) {
            return new ReadOnlyProjectConfigData([], $this);
        }

        if (version_compare(Info::fetch()->schemaVersion, '3.4.4', '<')) {
            /** @phpstan-ignore-next-line */
            $config = Info::fetch()->config;

            $data = [];

            if ($config) {
                // Try to decode it in case it contains any 4+ byte characters
                $config = Str::decdec($config);
                if (str_starts_with($config, '{')) {
                    $data = Json::decode($config);
                } else {
                    $data = unserialize($config, ['allowed_classes' => false]);
                }
            }

            return new ReadOnlyProjectConfigData($data, $this);
        }

        // See if we can get away with using the cached data
        $data = DependencyCache::remember(self::STORED_CACHE_KEY, $this->cacheDuration, function () {
            $data = [];
            // Load the project config data
            $rows = DB::table(Table::PROJECTCONFIG)->orderBy('path')->pluck('value', 'path');

            foreach ($rows as $path => $value) {
                $current = &$data;
                $segments = explode('.', $path);
                foreach ($segments as $segment) {
                    // If we're still traversing, enforce array to avoid errors.
                    /** @phpstan-ignore-next-line */
                    if (! is_array($current)) {
                        $current = [];
                    }
                    /** @phpstan-ignore-next-line */
                    if (! array_key_exists($segment, $current)) {
                        $current[$segment] = [];
                    }
                    $current = &$current[$segment];
                }
                $current = Json::decode(Str::decdec($value));
            }

            return ProjectConfigHelper::cleanupConfig($data);
        }, $this->getCacheDependency());

        return new ReadOnlyProjectConfigData($data, $this);
    }

    /**
     * Returns the cache dependency that should be used for project config caches.
     */
    public function getCacheDependency(): CallbackDependency
    {
        return new CallbackDependency(fn () => Info::fetch()->configVersion);
    }

    /**
     * Returns the system config array.
     */
    private function _systemConfig(array $data): array
    {
        $data['schemaVersion'] = Info::fetch()->schemaVersion;

        return $data;
    }

    /**
     * Return site data config array.
     */
    private function _getSiteGroupData(): array
    {
        return SiteGroups::getAllGroups()
            ->mapWithKeys(fn (SiteGroup $group) => [$group->uid => $group->getConfig()])
            ->all();
    }

    /**
     * Return site data config array.
     */
    private function _getSiteData(): array
    {
        return Sites::getAllSites(true)
            ->mapWithKeys(fn (Site $site) => [$site->uid => $site->getConfig()])
            ->all();
    }

    /**
     * Return section data config array.
     */
    private function _getSectionData(): array
    {
        return Sections::getAllSections()
            ->mapWithKeys(fn (Section $section) => [$section->uid => $section->getConfig()])
            ->all();
    }

    /**
     * Returns element source data.
     */
    private function _getElementSourceData(array $sourceConfigs): array
    {
        foreach ($sourceConfigs as &$elementTypeConfigs) {
            foreach ($elementTypeConfigs as &$config) {
                if ($config['type'] === ElementSources::TYPE_CUSTOM && isset($config['condition'])) {
                    try {
                        $config['condition'] = Conditions::createCondition($config['condition'])->getConfig();
                    } catch (InvalidArgumentException|InvalidConfigException) {
                        // Ignore it
                    }
                }
            }
        }

        return $sourceConfigs;
    }

    /**
     * Return entry type data config array.
     */
    private function _getEntryTypeData(): array
    {
        return EntryTypes::getAllEntryTypes()
            ->mapWithKeys(fn (EntryType $entryType) => [$entryType->uid => $entryType->getConfig()])
            ->all();
    }

    /**
     * Returns filesystem config data.
     */
    private function _getFsData(): array
    {
        return Filesystems::getAllFilesystems()
            ->mapWithKeys(fn (FsInterface $fs) => [$fs->handle => Filesystems::createFilesystemConfig($fs)])
            ->all();
    }

    /**
     * Return field data config array.
     */
    private function _getFieldData(): array
    {
        return Fields::getAllFields('global')
            ->mapWithKeys(fn (FieldInterface $field) => [$field->uid => Fields::createFieldConfig($field)])
            ->all();
    }

    /**
     * Return volume data config array.
     */
    private function _getVolumeData(): array
    {
        return Volumes::getAllVolumes()
            ->mapWithKeys(fn (Volume $volume) => [$volume->uid => $volume->getConfig()])
            ->all();
    }

    /**
     * Return user data config array.
     */
    private function _getUserData(array $data): array
    {
        $fieldLayout = Fields::getLayoutByType(User::class, false);
        $fieldLayoutConfig = $fieldLayout?->getConfig();

        if ($fieldLayoutConfig) {
            $data['fieldLayouts'] = [
                $fieldLayout->uid => $fieldLayoutConfig,
            ];
        } else {
            unset($data['fieldLayouts']);
        }

        $data['groups'] = [];

        foreach (UserGroups::getAllGroups() as $group) {
            $data['groups'][$group->uid] = $group->getConfig();
        }

        return $data;
    }

    /**
     * Return addresses data config array.
     */
    private function _getAddressesData(): array
    {
        $data = [];
        $fieldLayout = Fields::getLayoutByType(Address::class, false);
        $fieldLayoutConfig = $fieldLayout?->getConfig();

        if ($fieldLayoutConfig) {
            $data['fieldLayouts'] = [
                $fieldLayout->uid => $fieldLayoutConfig,
            ];
        }

        return $data;
    }

    /**
     * Return plugin data config array
     */
    private function _getPluginData(array $currentPluginData): array
    {
        return DB::table(Table::PLUGINS)
            ->select(['handle', 'schemaVersion'])
            ->get()
            ->mapWithKeys(fn (object $plugin) => [$plugin->handle => array_merge(
                $currentPluginData[$plugin->handle] ?? [],
                ['schemaVersion' => $plugin->schemaVersion],
            )])
            ->all();
    }

    /**
     * Return asset transform config array
     */
    private function _getTransformData(): array
    {
        return app(ImageTransforms::class)->getAllTransforms()
            ->mapWithKeys(fn (ImageTransform $transform) => [$transform->uid => $transform->getConfig()])
            ->all();
    }

    /**
     * Return GraphQL config array
     */
    private function _getGqlData(): array
    {
        $gqlService = Craft::$app->getGql();
        $publicToken = $gqlService->getPublicToken();

        $data = [
            'schemas' => [],
            'publicToken' => [
                'enabled' => $publicToken->enabled ?? false,
                'expiryDate' => ($publicToken->expiryDate ?? false) ? $publicToken->expiryDate->getTimestamp() : null,
            ],
        ];

        foreach ($gqlService->getSchemas() as $schema) {
            $data['schemas'][$schema->uid] = $schema->getConfig();
        }

        return $data;
    }

    /**
     * Acquires a mutex lock on the project config, and then ensures that we’ve actually got the latest
     * and greatest version of it.
     *
     * @throws BusyResourceException if a lock could not be acquired
     * @throws StaleResourceException if the loaded project config is out-of-date
     */
    private function _acquireLock(): void
    {
        if ($this->_locked) {
            return;
        }

        $mutex = Cache::lock(self::MUTEX_NAME, 30);

        if (! $mutex->get()) {
            throw new BusyResourceException('A lock could not be acquired to modify the project config.');
        }

        if (Cms::isInstalled()) {
            try {
                $storedConfigVersion = DB::table(Table::INFO)->value('configVersion');
            } catch (Throwable) {
                $storedConfigVersion = null;
            }

            if ($storedConfigVersion && $storedConfigVersion !== Info::fetch()->configVersion) {
                // Another request must have updated the project config after this request began
                $mutex->release();
                throw new StaleResourceException('The loaded project config is out-of-date.');
            }
        }

        $this->_locked = true;
    }

    /**
     * Releases the mutex lock on the project config.
     */
    private function _releaseLock(): void
    {
        if (! $this->_locked) {
            return;
        }

        Cache::lock(self::MUTEX_NAME)->forceRelease();
        $this->_locked = false;
    }
}
