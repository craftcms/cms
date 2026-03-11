<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\events\ConfigEvent;
use craft\events\RebuildConfigEvent;
use CraftCms\Cms\ProjectConfig\Events\ChangesApplied;
use CraftCms\Cms\ProjectConfig\Events\ItemAdded;
use CraftCms\Cms\ProjectConfig\Events\ItemRemoved;
use CraftCms\Cms\ProjectConfig\Events\ItemUpdated;
use CraftCms\Cms\ProjectConfig\Events\RebuildConfig;
use CraftCms\Cms\ProjectConfig\Events\YamlFilesWritten;
use CraftCms\Cms\ProjectConfig\Exceptions\BusyResourceException;
use CraftCms\Cms\ProjectConfig\Exceptions\StaleResourceException;
use CraftCms\DependencyAwareCache\Dependency\CallbackDependency;
use Illuminate\Support\Facades\Event;
use Throwable;
use yii\base\Component;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\web\ServerErrorHttpException;

/**
 * Project Config service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getProjectConfig()|`Craft::$app->getProjectConfig()`]].
 *
 * @property-read bool $isApplyingExternalChanges
 * @property-read bool $isApplyingYamlChanges
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\ProjectConfig\ProjectConfig} instead.
 */
class ProjectConfig extends Component
{
    /**
     * The cache key that is used to store the modified time of the project config files, at the time they were last applied.
     */
    public const CACHE_KEY = \CraftCms\Cms\ProjectConfig\ProjectConfig::CACHE_KEY;
    /**
     * The cache key that is used to store the modified time of the project config files, at the time they were last applied or ignored.
     *
     * @since 3.5.0
     * @deprecated in 5.6.6
     */
    public const IGNORE_CACHE_KEY = 'projectConfig:ignore';
    /**
     * The cache key that is used to store the loaded project config data.
     */
    public const STORED_CACHE_KEY = \CraftCms\Cms\ProjectConfig\ProjectConfig::STORED_CACHE_KEY;
    /**
     * The cache key that is used to store whether there were any issues writing the project config files out.
     *
     * @since 3.5.0
     */
    public const FILE_ISSUES_CACHE_KEY = \CraftCms\Cms\ProjectConfig\ProjectConfig::FILE_ISSUES_CACHE_KEY;
    /**
     * The cache key that is used to store the current project config diff
     *
     * @since 3.5.8
     */
    public const DIFF_CACHE_KEY = \CraftCms\Cms\ProjectConfig\ProjectConfig::DIFF_CACHE_KEY;
    /**
     * The duration that project config caches should be cached.
     */
    public const CACHE_DURATION = \CraftCms\Cms\ProjectConfig\ProjectConfig::CACHE_DURATION;
    /**
     * @var string Filename for base config file
     * @since 3.1.0
     */
    public const CONFIG_FILENAME = \CraftCms\Cms\ProjectConfig\ProjectConfig::CONFIG_FILENAME;
    /**
     * Filename for base config delta files
     *
     * @since 3.4.0
     */
    public const CONFIG_DELTA_FILENAME = \CraftCms\Cms\ProjectConfig\ProjectConfig::CONFIG_DELTA_FILENAME;
    /**
     * The array key to use for signaling ordered-to-associative array conversion.
     */
    public const ASSOC_KEY = \CraftCms\Cms\ProjectConfig\ProjectConfig::ASSOC_KEY;

    /**
     * @see _acquireLock()
     * @see _releaseLock()
     * @since 3.7.35
     */
    public const MUTEX_NAME = \CraftCms\Cms\ProjectConfig\ProjectConfig::MUTEX_NAME;

    public const PATH_ADDRESSES = \CraftCms\Cms\ProjectConfig\ProjectConfig::PATH_ADDRESSES;
    public const PATH_ADDRESS_FIELD_LAYOUTS = \CraftCms\Cms\ProjectConfig\ProjectConfig::PATH_ADDRESS_FIELD_LAYOUTS;
    /** @deprecated in 6.0.0 */
    public const PATH_CATEGORY_GROUPS = 'categoryGroups';
    public const PATH_DATE_MODIFIED = \CraftCms\Cms\ProjectConfig\ProjectConfig::PATH_DATE_MODIFIED;
    public const PATH_ELEMENT_SOURCES = \CraftCms\Cms\ProjectConfig\ProjectConfig::PATH_ELEMENT_SOURCES;
    public const PATH_ENTRY_TYPES = \CraftCms\Cms\ProjectConfig\ProjectConfig::PATH_ENTRY_TYPES;
    public const PATH_FIELDS = \CraftCms\Cms\ProjectConfig\ProjectConfig::PATH_FIELDS;
    /** @deprecated in 6.0.0 */
    public const PATH_GLOBAL_SETS = 'globalSets';
    public const PATH_FS = \CraftCms\Cms\ProjectConfig\ProjectConfig::PATH_FS;
    public const PATH_GRAPHQL = \CraftCms\Cms\ProjectConfig\ProjectConfig::PATH_GRAPHQL;
    public const PATH_GRAPHQL_PUBLIC_TOKEN = \CraftCms\Cms\ProjectConfig\ProjectConfig::PATH_GRAPHQL_PUBLIC_TOKEN;
    public const PATH_GRAPHQL_SCHEMAS = \CraftCms\Cms\ProjectConfig\ProjectConfig::PATH_GRAPHQL_SCHEMAS;
    public const PATH_IMAGE_TRANSFORMS = \CraftCms\Cms\ProjectConfig\ProjectConfig::PATH_IMAGE_TRANSFORMS;
    /** @since 4.4.17 */
    public const PATH_META = \CraftCms\Cms\ProjectConfig\ProjectConfig::PATH_META;
    public const PATH_META_NAMES = \CraftCms\Cms\ProjectConfig\ProjectConfig::PATH_META_NAMES;
    public const PATH_PLUGINS = \CraftCms\Cms\ProjectConfig\ProjectConfig::PATH_PLUGINS;
    public const PATH_ROUTES = \CraftCms\Cms\ProjectConfig\ProjectConfig::PATH_ROUTES;
    public const PATH_SCHEMA_VERSION = \CraftCms\Cms\ProjectConfig\ProjectConfig::PATH_SCHEMA_VERSION;
    public const PATH_SECTIONS = \CraftCms\Cms\ProjectConfig\ProjectConfig::PATH_SECTIONS;
    public const PATH_SITES = \CraftCms\Cms\ProjectConfig\ProjectConfig::PATH_SITES;
    public const PATH_SITE_GROUPS = \CraftCms\Cms\ProjectConfig\ProjectConfig::PATH_SITE_GROUPS;
    public const PATH_SYSTEM = \CraftCms\Cms\ProjectConfig\ProjectConfig::PATH_SYSTEM;
    /** @deprecated in 6.0.0 */
    public const PATH_TAG_GROUPS = 'tagGroups';
    public const PATH_USERS = \CraftCms\Cms\ProjectConfig\ProjectConfig::PATH_USERS;
    public const PATH_USER_FIELD_LAYOUTS = \CraftCms\Cms\ProjectConfig\ProjectConfig::PATH_USER_FIELD_LAYOUTS;
    public const PATH_USER_GROUPS = \CraftCms\Cms\ProjectConfig\ProjectConfig::PATH_USER_GROUPS;
    public const PATH_VOLUMES = \CraftCms\Cms\ProjectConfig\ProjectConfig::PATH_VOLUMES;

    // Regexp patterns
    // -------------------------------------------------------------------------

    /**
     * Regexp pattern to determine a string that could be used as an UID.
     */
    public const UID_PATTERN = \CraftCms\Cms\ProjectConfig\ProjectConfig::UID_PATTERN;

    // Events
    // -------------------------------------------------------------------------

    /**
     * @event ConfigEvent The event that is triggered when an item is added to the config.
     *
     * ---
     *
     * ```php
     * use craft\events\ParseConfigEvent;
     * use CraftCms\Cms\ProjectConfig\ProjectConfig;
     * use yii\base\Event;
     *
     * Event::on(ProjectConfig::class, ProjectConfig::EVENT_ADD_ITEM, function(ParseConfigEvent $e) {
     *     // Ensure the item is also added in the database...
     * });
     * ```
     * @deprecated 6.0.0 use {@see ItemAdded} instead.
     */
    public const EVENT_ADD_ITEM = 'addItem';

    /**
     * @event ConfigEvent The event that is triggered when an item is updated in the config.
     *
     * ---
     *
     * ```php
     * use craft\events\ParseConfigEvent;
     * use CraftCms\Cms\ProjectConfig\ProjectConfig;
     * use yii\base\Event;
     *
     * Event::on(ProjectConfig::class, ProjectConfig::EVENT_UPDATE_ITEM, function(ParseConfigEvent $e) {
     *     // Ensure the item is also updated in the database...
     * });
     * ```
     * @deprecated 6.0.0 use {@see ItemUpdated} instead.
     */
    public const EVENT_UPDATE_ITEM = 'updateItem';

    /**
     * @event ConfigEvent The event that is triggered when an item is removed from the config.
     *
     * ---
     *
     * ```php
     * use craft\events\ParseConfigEvent;
     * use CraftCms\Cms\ProjectConfig\ProjectConfig;
     * use yii\base\Event;
     *
     * Event::on(ProjectConfig::class, ProjectConfig::EVENT_REMOVE_ITEM, function(ParseConfigEvent $e) {
     *     // Ensure the item is also removed in the database...
     * });
     * ```
     * @deprecated 6.0.0 use {@see ItemRemoved} instead.
     */
    public const EVENT_REMOVE_ITEM = 'removeItem';

    /**
     * @event Event The event that is triggered after pending project config file changes have been applied.
     * @deprecated 6.0.0 use {@see ChangesApplied} instead.
     */
    public const EVENT_AFTER_APPLY_CHANGES = 'afterApplyChanges';

    /**
     * @event Event The event that is triggered after the YAML files have been written out.
     * @since 4.8.0
     * @deprecated 6.0.0 use {@see YamlFilesWritten} instead.
     */
    public const EVENT_AFTER_WRITE_YAML_FILES = 'afterWriteYamlFiles';

    /**
     * @event RebuildConfigEvent The event that is triggered when the project config is being rebuilt.
     *
     * ---
     *
     * ```php
     * use craft\events\RebuildConfigEvent;
     * use CraftCms\Cms\ProjectConfig\ProjectConfig;
     * use yii\base\Event;
     *
     * Event::on(ProjectConfig::class, ProjectConfig::EVENT_REBUILD, function(RebuildConfigEvent $e) {
     *     // Add plugin’s project config data...
     *    $e->config['myPlugin']['key'] = $value;
     * });
     * ```
     *
     * @since 3.1.20
     * @deprecated 6.0.0 use {@see RebuildConfig} instead.
     */
    public const EVENT_REBUILD = 'rebuild';

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
     * @since 3.5.13
     */
    public bool $writeYamlAutomatically = true;

    /**
     * @var string The folder name to save the project config files in, within the `config/` folder.
     * @since 3.5.0
     */
    public string $folderName = 'project';

    /**
     * @var int The maximum number of project.yaml deltas to store in storage/config-deltas/
     * @since 3.4.0
     */
    public int $maxDeltas = 50;

    /**
     * @var int The maximum number of times deferred events can be re-deferred before we give up on them
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
     * @since 3.1.2
     */
    public bool $muteEvents = false;

    /**
     * @var bool Whether project config should force updates on entries that aren't new or being removed.
     */
    public bool $forceUpdate = false;

    /**
     * @var int|null The project config cache duration. If null, the <config5:cacheDuration> config setting will be used.
     * @since 4.5.0
     */
    public ?int $cacheDuration = null;

    /**
     * Saves the modified project config state and writes out updated YAML files, if needed.
     *
     * @since 5.0.0
     */
    public function flush(): void
    {
        app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->flush();
    }

    /**
     * Resets the internal state.
     *
     * @internal
     */
    public function reset(): void
    {
        app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->reset();
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
     * @param string|null $path The config item path, or `null` if the entire config should be returned
     * @param bool $getFromExternalConfig whether data should be fetched from the working config instead of the loaded config. Defaults to `false`.
     *
     * @return mixed The config item value
     */
    public function get(?string $path = null, bool $getFromExternalConfig = false): mixed
    {
        return app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->get($path, $getFromExternalConfig);
    }

    /**
     * Finds all config items that pass a condition, and returns their paths and configs as key/value pairs.
     *
     * @param callable $callback
     * @param bool $fromExternalConfig whether to find config items in the external config
     *
     * @return array
     * @since 5.0.0
     */
    public function find(callable $callback, bool $fromExternalConfig = false): array
    {
        return app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->find($callback, $fromExternalConfig);
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
     * @param string|null $message A message describing the changes
     * @param bool $updateTimestamp Whether the `dateModified` value should be updated, if it hasn’t been updated yet for this request
     * @param bool $force Whether the update should be processed regardless of whether the value actually changed
     *
     * @return bool Whether the project config was modified
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
        return app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->set(
            $path,
            $value,
            $message,
            $updateTimestamp,
            $force,
        );
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
    public function remove(string $path, ?string $message = null): void
    {
        app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->remove($path, $message);
    }

    /**
     * Regenerates the external config based on the loaded project config.
     *
     * @since 4.0.0
     */
    public function regenerateExternalConfig(): void
    {
        app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->regenerateExternalConfig();
    }

    /**
     * Applies changes in external config to project config.
     *
     * @throws BusyResourceException if a lock could not be acquired
     * @throws StaleResourceException if the loaded project config is out-of-date
     * @since 4.0.0
     */
    public function applyExternalChanges(): void
    {
        app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->applyExternalChanges();
    }

    /**
     * Applies given changes to the project config.
     *
     * @param array $configData
     */
    public function applyConfigChanges(array $configData): void
    {
        app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->applyConfigChanges($configData);
    }

    /**
     * Returns whether external changes are currently being applied
     *
     * @return bool
     * @since 4.0.0
     */
    public function getIsApplyingExternalChanges(): bool
    {
        return app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->isApplyingExternalChanges;
    }

    /**
     * Returns whether external project config files appear to exist.
     *
     * @return bool
     * @since 4.0.0
     */
    public function getDoesExternalConfigExist(): bool
    {
        return app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->getDoesExternalConfigExist();
    }

    /**
     * Returns whether a given path has pending changes that need to be applied to the loaded project config.
     *
     * @param string|null $path A specific config path that should be checked for pending changes.
     * If this is null, then `true` will be returned if there are *any* pending changes in external config.
     * @param bool $force Whether to check for changes even if it doesn’t look like anything has changed since
     * the last time [[ignorePendingChanges()]] has been called.
     *
     * @return bool
     */
    public function areChangesPending(?string $path = null, bool $force = false): bool
    {
        return app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->areChangesPending($path, $force);
    }

    /**
     * Processes changes in the project config files for a given config item path.
     *
     * Note that this will only have an effect if external project config changes are currently getting [[getIsApplyingExternalChanges()|applied]].
     *
     * @param string $path The config item path
     * @param bool $force Whether the config change should be processed regardless of previous records,
     * or whether external changes are currently being applied
     */
    public function processConfigChanges(string $path, bool $force = false): void
    {
        app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->processConfigChanges($path, $force);
    }

    /**
     * Updates cached config file modified times after the request ends.
     */
    public function updateParsedConfigTimesAfterRequest(): void
    {
        app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->updateParsedConfigTimesAfterRequest();
    }

    /**
     * Ignores any pending changes in the project config files.
     *
     * @since 3.5.0
     * @deprecated in 5.6.6
     */
    public function ignorePendingChanges(): void
    {
    }

    /**
     * Updates cached config file modified times immediately.
     *
     * @return bool
     */
    public function updateParsedConfigTimes(): bool
    {
        return app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->updateParsedConfigTimes();
    }

    /**
     * Saves all the config data that has been modified up to now.
     *
     * @throws ErrorException
     */
    public function saveModifiedConfigData(): void
    {
        app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->saveModifiedConfigData();
    }

    /**
     * Returns a summary of all pending config changes.
     *
     * @return array
     */
    public function getPendingChangeSummary(): array
    {
        return app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->getPendingChangeSummary();
    }

    /**
     * Get the list of applied changes
     *
     * @return array
     * @since 5.1.0
     */
    public function getAppliedChanges(): array
    {
        return app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->getAppliedChanges();
    }

    /**
     * Returns whether all schema versions stored in the config are compatible with the actual codebase.
     * The schemas must match exactly to avoid unpredictable behavior that can occur when running migrations
     * and applying project config changes at the same time.
     *
     * @param array $issues Passed by reference and populated with issues on error in
     *                      the following format: `[$pluginName, $existingSchema, $incomingSchema]`
     *
     * @return bool
     */
    public function getAreConfigSchemaVersionsCompatible(array &$issues = []): bool
    {
        return app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->getAreConfigSchemaVersionsCompatible($issues);
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
     * @param string $path The config path pattern. Can contain `{uri}` tokens, which will be passed to the handler.
     * @param callable $handler The handler method.
     * @param mixed $data The data to be passed to the event handler when the event is triggered.
     * When the event handler is invoked, this data can be accessed via [[ConfigEvent::data]].
     *
     * @return static self reference
     */
    public function onAdd(string $path, callable $handler, mixed $data = null): self
    {
        app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->onAdd($path, function(ItemAdded $event) use ($handler) {
            $yiiEvent = new ConfigEvent([
                'path' => $event->path,
                'oldValue' => $event->oldValue,
                'newValue' => $event->newValue,
                'tokenMatches' => $event->tokenMatches,
            ]);

            return $handler($yiiEvent);
        }, $data);

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
     *
     * @return static self reference
     */
    public function onUpdate(string $path, callable $handler, mixed $data = null): self
    {
        app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->onUpdate($path, function(ItemUpdated $event) use ($handler) {
            $yiiEvent = new ConfigEvent([
                'path' => $event->path,
                'oldValue' => $event->oldValue,
                'newValue' => $event->newValue,
                'tokenMatches' => $event->tokenMatches,
            ]);

            return $handler($yiiEvent);
        }, $data);

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
     *
     * @return static self reference
     */
    public function onRemove(string $path, callable $handler, mixed $data = null): self
    {
        app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->onRemove($path, function(ItemRemoved $event) use ($handler) {
            $yiiEvent = new ConfigEvent([
                'path' => $event->path,
                'oldValue' => $event->oldValue,
                'newValue' => $event->newValue,
                'tokenMatches' => $event->tokenMatches,
            ]);

            return $handler($yiiEvent);
        }, $data);

        return $this;
    }

    /**
     * Defers an event until all other project config changes have been processed.
     *
     * @param ConfigEvent $event
     * @param callable $handler
     *
     * @since 3.1.13
     */
    public function defer(ConfigEvent $event, callable $handler): void
    {
        $newEvent = match ($event->name) {
            self::EVENT_ADD_ITEM => new ItemAdded($event->path, $event->oldValue, $event->newValue, $event->tokenMatches, $event->data),
            self::EVENT_UPDATE_ITEM => new ItemUpdated($event->path, $event->oldValue, $event->newValue, $event->tokenMatches, $event->data),
            self::EVENT_REMOVE_ITEM => new ItemRemoved($event->path, $event->oldValue, $event->newValue, $event->tokenMatches, $event->data),
            default => null,
        };

        if (!$newEvent) {
            return;
        }

        app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->defer($newEvent, fn() => $handler($event));
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
    public function registerChangeEventHandler(string $event, string $path, callable $handler, mixed $data = null): void
    {
        app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->registerChangeEventHandler($event, $path, $handler, $data);
    }

    /**
     * Handles a config change event.
     *
     * @param ConfigEvent $event
     *
     * @since 3.4.0
     */
    public function handleChangeEvent(ConfigEvent $event): void
    {
        $newEvent = match ($event->name) {
            self::EVENT_ADD_ITEM => new ItemAdded($event->path, $event->oldValue, $event->newValue, $event->tokenMatches, $event->data),
            self::EVENT_UPDATE_ITEM => new ItemUpdated($event->path, $event->oldValue, $event->newValue, $event->tokenMatches, $event->data),
            self::EVENT_REMOVE_ITEM => new ItemRemoved($event->path, $event->oldValue, $event->newValue, $event->tokenMatches, $event->data),
            default => null,
        };

        if (!$newEvent) {
            return;
        }

        app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->handleChangeEvent($newEvent);
    }

    /**
     * Rebuilds the project config from the current state in the database.
     *
     * @throws BusyResourceException if a lock could not be acquired
     * @throws StaleResourceException if the loaded project config is out-of-date
     * @throws Throwable if reasons
     * @since 3.1.20
     */
    public function rebuild(): void
    {
        app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->rebuild();
    }

    /**
     * Update the config YAML files with the buffered changes.
     *
     * @param bool $force Whether to write out the YAML even if there aren’t any new changes
     *
     * @throws Exception if something goes wrong
     * @since 5.0.0
     */
    public function writeYamlFiles(bool $force = false): void
    {
        app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->writeYamlFiles($force);
    }

    /**
     * Sets a UUID/name mapping on the working config.
     *
     * @param string $uid
     * @param string $name
     *
     * @since 4.4.17
     */
    public function setNameMapping(string $uid, string $name): void
    {
        app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->setNameMapping($uid, $name);
    }

    /**
     * Removes a UUID/name mapping on the working config.
     *
     * @param string $uid
     *
     * @since 4.4.17
     */
    public function removeNameMapping(string $uid): void
    {
        app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->removeNameMapping($uid);
    }

    /**
     * Returns whether we have a record of issues writing out files to the project config folder.
     *
     * @return bool
     * @since 3.5.0
     */
    public function getHadFileWriteIssues(): bool
    {
        return app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->getHadFileWriteIssues();
    }

    /**
     * Update Craft's internal config store for a path with the new value. If the value
     * is null, it will be removed instead.
     *
     * @param string $path
     * @param mixed $oldValue
     * @param mixed $newValue
     * @param string|null $message message describing the changes made.
     *
     * @since 4.0.0
     */
    public function rememberAppliedChanges(
        string $path,
        mixed $oldValue,
        mixed $newValue,
        ?string $message = null,
    ): void {
        app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->rememberAppliedChanges($path, $oldValue, $newValue, $message);
    }

    /**
     * Returns the cache dependency that should be used for project config caches.
     *
     * @return CallbackDependency
    */
    public function getCacheDependency(): CallbackDependency
    {
        return app(\CraftCms\Cms\ProjectConfig\ProjectConfig::class)->getCacheDependency();
    }

    public static function registerEvents(): void
    {
        Event::listen(ChangesApplied::class, function() {
            Craft::$app->getProjectConfig()->trigger(self::EVENT_AFTER_APPLY_CHANGES);
        });

        Event::listen(YamlFilesWritten::class, function() {
            Craft::$app->getProjectConfig()->trigger(self::EVENT_AFTER_WRITE_YAML_FILES);
        });

        Event::listen(RebuildConfig::class, function(RebuildConfig $event) {
            $yiiEvent = new RebuildConfigEvent(['config' => $event->config]);

            Craft::$app->getProjectConfig()->trigger(self::EVENT_REBUILD, $yiiEvent);

            $event->config = $yiiEvent->config;
        });

        Event::listen(ItemAdded::class, function(ItemAdded $event) {
            $yiiEvent = new ConfigEvent([
                'path' => $event->path,
                'oldValue' => $event->oldValue,
                'newValue' => $event->newValue,
                'tokenMatches' => $event->tokenMatches,
            ]);

            Craft::$app->getProjectConfig()->trigger(self::EVENT_ADD_ITEM, $yiiEvent);
        });

        Event::listen(ItemRemoved::class, function(ItemRemoved $event) {
            $yiiEvent = new ConfigEvent([
                'path' => $event->path,
                'oldValue' => $event->oldValue,
                'newValue' => $event->newValue,
                'tokenMatches' => $event->tokenMatches,
            ]);

            Craft::$app->getProjectConfig()->trigger(self::EVENT_REMOVE_ITEM, $yiiEvent);
        });

        Event::listen(ItemUpdated::class, function(ItemUpdated $event) {
            $yiiEvent = new ConfigEvent([
                'path' => $event->path,
                'oldValue' => $event->oldValue,
                'newValue' => $event->newValue,
                'tokenMatches' => $event->tokenMatches,
            ]);

            Craft::$app->getProjectConfig()->trigger(self::EVENT_UPDATE_ITEM, $yiiEvent);
        });
    }
}
