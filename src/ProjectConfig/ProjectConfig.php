<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\ProjectConfig\Events\ChangesApplied;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\Events\ItemAdded;
use CraftCms\Cms\ProjectConfig\Events\ItemRemoved;
use CraftCms\Cms\ProjectConfig\Events\ItemUpdated;
use CraftCms\Cms\ProjectConfig\Events\ProjectConfigItemAdding;
use CraftCms\Cms\ProjectConfig\Events\ProjectConfigItemRemoved;
use CraftCms\Cms\ProjectConfig\Events\ProjectConfigItemUpdated;
use CraftCms\Cms\ProjectConfig\Events\ProjectConfigRebuilt;
use CraftCms\Cms\ProjectConfig\Exceptions\BusyResourceException;
use CraftCms\Cms\ProjectConfig\Exceptions\ReadonlyException;
use CraftCms\Cms\ProjectConfig\Exceptions\StaleResourceException;
use CraftCms\Cms\Shared\Models\Info;
use CraftCms\Cms\Support\Str;
use CraftCms\DependencyAwareCache\Dependency\CallbackDependency;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PDOException;
use Throwable;

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
     * The duration that project config caches should be cached, in seconds. Defaults to one year.
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
     * @see acquireLock()
     * @see releaseLock()
     */
    public const string MUTEX_NAME = 'project-config';

    /**
     * Pattern for `{uid}` tokens, which match UUIDs as well as component handles.
     */
    public const string UID_PATTERN = '[a-zA-Z0-9_-]+';

    public const string PATH_ADDRESSES = 'addresses';

    public const string PATH_ADDRESS_FIELD_LAYOUTS = self::PATH_ADDRESSES.'.'.'fieldLayouts';

    public const string PATH_ASSET_TRANSFORMERS = 'assetTransformers';

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

    /**
     * @var bool Whether project config changes should be written to YAML files automatically.
     *
     * If set to `false`, you can manually write out project config YAML files using the `craft:project-config/write` command.
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
     * @see applyConfigChanges()
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
     * @var int|null The project config cache duration. If null, the [[GeneralConfig::cacheDuration]] config setting will be used.
     */
    public ?int $cacheDuration = null;

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

    /** @var array<string|int, mixed>|null */
    private ?array $current = null;

    /** @var array<string|int, mixed>|null */
    private ?array $external = null;

    /** @var array<string|int, mixed>|null */
    private ?array $original = null;

    /** @var array<string, true> */
    private array $claimedPaths = [];

    /** @var array<string, true> */
    private array $processedPaths = [];

    /** @var list<array{added?: array<string, mixed>, removed?: array<string, mixed>, message?: string}> */
    private array $changes = [];

    private int $persistedChanges = 0;

    private int $loggedChanges = 0;

    private int $generation = 0;

    private bool $timestampUpdated = false;

    private bool $yamlDirty = false;

    private ?Lock $lock = null;

    private readonly ChangeHandlers $handlers;

    private readonly ConfigStorage $storage;

    public function __construct(GeneralConfig $generalConfig)
    {
        $this->readOnly = Cms::isInstalled() && ! $generalConfig->allowAdminChanges;
        $this->writeYamlAutomatically = ! app()->isEphemeral();
        $this->handlers = new ChangeHandlers;
        $this->storage = new ConfigStorage;

        foreach ([ItemAdded::class, ItemUpdated::class, ItemRemoved::class] as $event) {
            Event::listen($event, $this->handleChangeEvent(...));
        }
    }

    /**
     * Sets whether project config changes should be written to YAML files automatically.
     *
     * If set to `false`, you can manually write out project config YAML files using the `craft:project-config/write` command.
     *
     * ::: warning
     * If this is set to `false`, Craft won’t have a strong grasp of whether the YAML files or database contain the most relevant
     * project config data, so there’s a chance that the Project Config utility will be a bit misleading.
     * :::
     *
     * @see flush()
     */
    public function writeYamlAutomatically(bool $writeYamlAutomatically = true): self
    {
        $this->writeYamlAutomatically = $writeYamlAutomatically;

        return $this;
    }

    /**
     * Resets the internal state.
     *
     * @internal
     */
    public function reset(): void
    {
        $this->current = null;
        $this->external = null;
        $this->original = null;
        $this->changes = [];
        $this->persistedChanges = 0;
        $this->loggedChanges = 0;
        $this->processedPaths = [];
        $this->timestampUpdated = false;
        $this->yamlDirty = false;
        $this->isApplyingExternalChanges = false;
        $this->generation++;
        $this->resetClaimedPaths();
        $this->handlers->reset();
    }

    /**
     * Claims a config path before processing to prevent recursive processing.
     *
     * @internal
     */
    public function claimPath(string $path, bool $force = false): bool
    {
        if ((! $this->isApplyingExternalChanges && ! $force) || isset($this->claimedPaths[$path])) {
            return false;
        }

        $this->claimedPaths[$path] = true;

        return true;
    }

    /**
     * Clears claimed paths for a new application or an explicit retry after failure.
     *
     * @internal
     */
    public function resetClaimedPaths(): void
    {
        $this->claimedPaths = [];
    }

    /**
     * Returns a config item value by its path.
     *
     * ---
     *
     * ```php
     * use CraftCms\Cms\Support\Facades\ProjectConfig;
     *
     * $value = ProjectConfig::get('foo.bar');
     * ```
     *
     * @param  string|null  $path  The config item path, or `null` if the entire config should be returned
     * @param  bool  $getFromExternalConfig  Whether to read external YAML data instead of the working config. Defaults to `false`.
     * @return mixed The config item value
     */
    public function get(?string $path = null, bool $getFromExternalConfig = false): mixed
    {
        $this->current ??= $this->storage->readDatabase($this->cacheDuration);
        $this->original ??= $this->current;
        $data = $this->current;

        if ($getFromExternalConfig) {
            $data = $this->getHadFileWriteIssues()
                ? $this->current
                : (($this->external ??= $this->storage->readYaml($this->folderName)) ?? $this->current);
        }

        return $path === null ? $data : ProjectConfigHelper::traverseDataArray($data, $path);
    }

    /** @return array<string, array<string|int, mixed>> */
    public function find(callable $callback, bool $fromExternalConfig = false): array
    {
        $matches = [];
        $findMatches = function (array $data, string $path) use (&$findMatches, &$matches, $callback): void {
            foreach ($data as $key => $value) {
                if (! is_array($value)) {
                    continue;
                }

                $segment = str_replace('.', '\\.', (string) $key);
                $itemPath = $path === '' ? $segment : $path.'.'.$segment;

                if ($callback($value, $itemPath)) {
                    $matches[$itemPath] = $value;
                } else {
                    $findMatches($value, $itemPath);
                }
            }
        };
        $findMatches($this->get(null, $fromExternalConfig), '');

        return $matches;
    }

    /**
     * Attaches an event handler for when an item is added to the config at a given path.
     *
     * ---
     *
     * ```php
     * use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
     * use CraftCms\Cms\Support\Facades\ProjectConfig;
     * use Illuminate\Support\Facades\DB;
     *
     * ProjectConfig::onAdd('foo.{uid}', function (ConfigEvent $event) {
     *     DB::table("example_items")->updateOrInsert(
     *         ["uid" => $event->tokenMatches[0]],
     *         $event->newValue,
     *     );
     * });
     * ```
     *
     * @param  string  $path  The config path pattern. Can contain `{uid}` tokens, which will be passed to the handler.
     * @param  callable  $handler  The handler method.
     * @param  mixed  $data  The data to be passed to the event handler when the event is triggered.
     *                       When the event handler is invoked, this data can be accessed via [[ConfigEvent::data]].
     * @return self Self reference
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
     * use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
     * use CraftCms\Cms\Support\Facades\ProjectConfig;
     * use Illuminate\Support\Facades\DB;
     *
     * ProjectConfig::onUpdate('foo.{uid}', function (ConfigEvent $event) {
     *     DB::table("example_items")
     *         ->where("uid", $event->tokenMatches[0])
     *         ->update($event->newValue);
     * });
     * ```
     *
     * @param  string  $path  The config path pattern. Can contain `{uid}` tokens, which will be passed to the handler.
     * @param  callable  $handler  The handler method.
     * @param  mixed  $data  The data to be passed to the event handler when the event is triggered.
     *                       When the event handler is invoked, this data can be accessed via [[ConfigEvent::data]].
     * @return self Self reference
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
     * use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
     * use CraftCms\Cms\Support\Facades\ProjectConfig;
     * use Illuminate\Support\Facades\DB;
     *
     * ProjectConfig::onRemove('foo.{uid}', function (ConfigEvent $event) {
     *     DB::table("example_items")
     *         ->where("uid", $event->tokenMatches[0])
     *         ->delete();
     * });
     * ```
     *
     * @param  string  $path  The config path pattern. Can contain `{uid}` tokens, which will be passed to the handler.
     * @param  callable  $handler  The handler method.
     * @param  mixed  $data  The data to be passed to the event handler when the event is triggered.
     *                       When the event handler is invoked, this data can be accessed via [[ConfigEvent::data]].
     * @return self Self reference
     */
    public function onRemove(string $path, callable $handler, mixed $data = null): self
    {
        $this->registerChangeEventHandler(ItemRemoved::class, $path, $handler, $data);

        return $this;
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
        $this->handlers->register($event, $path, $handler, $data);
    }

    /**
     * Handles a config change event.
     */
    public function handleChangeEvent(ConfigEvent $event): void
    {
        $this->handlers->dispatch($event, function (string $path, ConfigEvent $child): void {
            if (isset($this->processedPaths[$path])) {
                return;
            }

            $newValue = $this->get($path, $this->isApplyingExternalChanges);

            if (is_array($newValue)) {
                // The containing value may be stale; merge the child's new value before processing it.
                $relative = array_slice(ProjectConfigHelper::pathSegments($child->path), count(ProjectConfigHelper::pathSegments($path)));
                ProjectConfigHelper::traverseDataArray($newValue, $relative, $child->newValue);
            }

            $this->commit($path, $newValue);
        });
    }

    /**
     * Defers an event until all other project config changes have been processed.
     */
    public function defer(ConfigEvent $event, callable $handler): void
    {
        $this->handlers->defer($event, $handler);
    }

    /**
     * Sets a config item value at the given path.
     *
     * ---
     *
     * ```php
     * use CraftCms\Cms\Support\Facades\ProjectConfig;
     *
     * ProjectConfig::set('foo.bar', 'value');
     * ```
     *
     * @param  string  $path  The config item path
     * @param  mixed  $value  The config item value
     * @param  string|null  $message  A message describing the changes
     * @param  bool  $updateTimestamp  Whether the `dateModified` value should be updated, if it hasn’t been updated yet for this request
     * @param  bool  $force  Whether the update should be processed regardless of whether the value actually changed
     * @return bool Whether the project config was modified
     *
     * @throws \Exception
     * @throws ReadonlyException if the service is set to read-only mode
     * @throws \RuntimeException
     * @throws BusyResourceException if a lock could not be acquired
     * @throws StaleResourceException if the loaded project config is out-of-date
     */
    public function set(string $path, mixed $value, ?string $message = null, bool $updateTimestamp = true, bool $force = false): bool
    {
        $value = is_array($value) ? ProjectConfigHelper::cleanupConfig($value) : $value;
        $oldValue = $this->get($path);

        if (! $force && $oldValue === $value) {
            return false;
        }

        if ($this->isApplyingExternalChanges && $value === $this->get($path, true)) {
            // This value is already coming in through the external config being applied.
            return true;
        }

        if ($this->readOnly && $oldValue !== $value) {
            throw new ReadonlyException('Project config cannot be changed while in read-only mode.');
        }

        if ($oldValue !== $value) {
            $this->acquireLock();
        }

        try {
            $this->commit($path, $value, $message, $force);

            if ($updateTimestamp && ! $this->timestampUpdated && $oldValue !== $value) {
                $this->timestampUpdated = true;
                $this->commit(self::PATH_DATE_MODIFIED, now()->getTimestamp());
            }

            $this->yamlDirty = true;
        } catch (Throwable $exception) {
            $this->releaseLock();

            throw $exception;
        }

        return true;
    }

    /**
     * Removes a config item at the given path.
     *
     * ---
     * ```php
     * use CraftCms\Cms\Support\Facades\ProjectConfig;
     *
     * ProjectConfig::remove('foo.bar');
     * ```
     *
     * @param  string  $path  The config item path
     * @param  string|null  $message  The message describing changes.
     */
    public function remove(string $path, ?string $message = null): void
    {
        $this->set($path, null, $message);
    }

    /** @param array<string|int, mixed>|null $previousConfig */
    private function commit(string $path, mixed $value, ?string $message = null, bool $force = false, bool $triggerUpdate = false, ?array $previousConfig = null): void
    {
        $oldValue = $previousConfig === null ? $this->get($path) : ProjectConfigHelper::traverseDataArray($previousConfig, $path);
        $changed = $triggerUpdate || ProjectConfigHelper::encodeValueAsString($oldValue) !== ProjectConfigHelper::encodeValueAsString($value)
            || ($this->forceUpdate && ($oldValue !== null || $value !== null));
        $this->processedPaths[$path] = true;

        if ($changed && ! $this->muteEvents) {
            event($this->changeEvent($path, $oldValue, $value, before: true));
        }

        if (($changed || $force) && ! str_starts_with($path, self::PATH_META_NAMES)) {
            $this->updateNames(ProjectConfigHelper::lastPathSegment($path), $oldValue, $value);
        }

        if ($changed && ! $this->muteEvents) {
            event($this->changeEvent($path, $oldValue, $value));
        }

        $parent = $path;

        while (($parent = ProjectConfigHelper::pathWithoutLastSegment($parent)) !== null) {
            $this->processedPaths[$parent] = true;
        }

        if ($changed) {
            $this->rememberAppliedChanges($path, $oldValue, $value, $message);
            ProjectConfigHelper::traverseDataArray($this->current, $path, $value, $value === null);

            if ($this->writeYamlAutomatically) {
                $this->updateParsedConfigTimesAfterRequest();
            }
        }
    }

    private function changeEvent(string $path, mixed $oldValue, mixed $newValue, bool $before = false): ConfigEvent
    {
        $class = match (true) {
            $newValue === null && $oldValue !== null => $before ? ProjectConfigItemRemoved::class : ItemRemoved::class,
            $oldValue === null && $newValue !== null => $before ? ProjectConfigItemAdding::class : ItemAdded::class,
            default => $before ? ProjectConfigItemUpdated::class : ItemUpdated::class,
        };

        return new $class($path, $oldValue, $newValue);
    }

    private function updateNames(string|int $key, mixed $old, mixed $new): void
    {
        $old = is_array($old) ? $old : [];
        $new = is_array($new) ? $new : [];

        if (Str::isUuid((string) $key)) {
            if (isset($new['name'])) {
                $this->setNameMapping($key, $new['name']);
            } elseif (isset($old['name'])) {
                $this->removeNameMapping($key);
            }
        }

        foreach (array_unique([...array_keys($old), ...array_keys($new)]) as $child) {
            $this->updateNames($child, $old[$child] ?? null, $new[$child] ?? null);
        }
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
        $path = self::PATH_META_NAMES.'.'.$uid;

        if ($this->readOnly || $this->get($path) === $name) {
            return;
        }

        $this->acquireLock();

        try {
            $this->commit($path, $name);
        } catch (Throwable $exception) {
            $this->releaseLock();

            throw $exception;
        }
    }

    /**
     * Records the added and removed values for a path in the change history.
     *
     * @param  string|null  $message  message describing the changes made.
     */
    public function rememberAppliedChanges(string $path, mixed $oldValue, mixed $newValue, ?string $message = null): void
    {
        $this->changes[] = ConfigChanges::history($path, $oldValue, $newValue, $message);
    }

    /** @return list<array{added?: array<string, mixed>, removed?: array<string, mixed>, message?: string}> */
    public function getAppliedChanges(): array
    {
        return $this->changes;
    }

    /**
     * Saves all the config data that has been modified up to now.
     */
    public function saveModifiedConfigData(): void
    {
        try {
            $end = count($this->changes);

            if ($this->persistedChanges < $end) {
                $start = $this->persistedChanges;
                $this->storage->save(array_slice($this->changes, $start));
                $this->persistedChanges = $end;

                if (DB::transactionLevel() > 0) {
                    $generation = $this->generation;
                    DB::afterRollBack(function () use ($start, $generation): void {
                        if ($this->generation === $generation) {
                            $this->persistedChanges = min($start, $this->persistedChanges);
                            Cache::forget(self::STORED_CACHE_KEY);
                        }
                    });
                }
            }

            if ($this->loggedChanges < $end) {
                $this->storage->writeDelta(array_slice($this->changes, $this->loggedChanges), $this->maxDeltas);
                $this->loggedChanges = $end;
            }
        } finally {
            $this->releaseLock();
        }
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

    private function acquireLock(): void
    {
        if ($this->lock !== null) {
            return;
        }

        $lock = Cache::lock(self::MUTEX_NAME, 30);

        if (! $lock->get()) {
            throw new BusyResourceException('Project config is being modified by another process.');
        }

        $this->lock = $lock;

        try {
            $version = DB::table(Table::INFO)->value('configVersion');
        } catch (PDOException) {
            return;
        }

        if ($version !== null && $version !== Info::fetch()->configVersion) {
            $this->releaseLock();

            throw new StaleResourceException('Project config has changed since this request started.');
        }
    }

    private function releaseLock(): void
    {
        $this->lock?->release();
        $this->lock = null;
    }

    /**
     * Returns the cache dependency that should be used for project config caches.
     */
    public function getCacheDependency(): CallbackDependency
    {
        return $this->storage->dependency();
    }

    /**
     * Returns whether external project config files appear to exist.
     */
    public function getDoesExternalConfigExist(): bool
    {
        return $this->storage->exists($this->folderName);
    }

    /**
     * Returns whether we have a record of issues writing out files to the project config folder.
     */
    public function getHadFileWriteIssues(): bool
    {
        return $this->writeYamlAutomatically && (bool) Cache::get(self::FILE_ISSUES_CACHE_KEY);
    }

    /**
     * Update the config YAML files with the buffered changes.
     *
     * @param  bool  $force  Whether to write out the YAML even if there aren’t any new changes
     *
     * @throws \Exception if something goes wrong
     */
    public function writeYamlFiles(bool $force = false): void
    {
        if (! $force && ! $this->yamlDirty) {
            return;
        }

        $this->storage->writeYaml($this->folderName, $this->get());
        $this->yamlDirty = false;
    }

    /**
     * Regenerates the external config based on the loaded project config.
     */
    public function regenerateExternalConfig(): void
    {
        $this->isApplyingExternalChanges = false;
        $this->saveModifiedConfigData();
        $this->updateParsedConfigTimesAfterRequest();
        $this->writeYamlFiles(true);
    }

    /**
     * Updates cached config file modified times after the request ends.
     */
    public function updateParsedConfigTimesAfterRequest(): void
    {
        $this->waitingToUpdateParsedConfigTimes = true;
    }

    /**
     * Updates cached config file modified times immediately.
     */
    public function updateParsedConfigTimes(): bool
    {
        return $this->storage->updateParsedTime($this->folderName);
    }

    /**
     * Returns whether a given path has pending changes that need to be applied to the loaded project config.
     *
     * @param  string|null  $path  A specific config path that should be checked for pending changes.
     *                             If this is null, then `true` will be returned if there are *any* pending changes in external config.
     * @param  bool  $force  Whether to check for changes even if it doesn’t look like anything has changed since
     *                       the last time the cached file modification time was updated.
     */
    public function areChangesPending(?string $path = null, bool $force = false): bool
    {
        if ($path !== null && isset($this->processedPaths[$path])) {
            return true;
        }

        if (! $this->getDoesExternalConfigExist() || $this->getHadFileWriteIssues()) {
            $this->writeYamlAutomatically ? $this->regenerateExternalConfig() : $this->saveModifiedConfigData();

            return false;
        }

        if (! $force && $this->storage->parsedTimeMatches($this->folderName)) {
            return false;
        }

        if ($path !== null) {
            $this->get();

            return ProjectConfigHelper::encodeValueAsString(ProjectConfigHelper::traverseDataArray($this->original, $path))
                !== ProjectConfigHelper::encodeValueAsString($this->get($path, true));
        }

        if (array_filter($this->getPendingChanges())) {
            Cache::forget(self::STORED_CACHE_KEY);

            return true;
        }

        $this->updateParsedConfigTimes();

        return false;
    }

    /**
     * @param  array<string|int, mixed>|null  $configData
     * @return array{newItems: list<string>, removedItems: list<string>, changedItems: list<string>}
     */
    public function getPendingChanges(?array $configData = null): array
    {
        return ConfigChanges::pending($this->get(), $configData ?? $this->get(null, true), $this->forceUpdate);
    }

    /**
     * Returns whether external project config changes are currently being applied.
     */
    public function isApplyingExternalChanges(): bool
    {
        return $this->isApplyingExternalChanges;
    }

    /**
     * Applies changes in external config to project config.
     *
     * @throws BusyResourceException if a lock could not be acquired
     * @throws StaleResourceException if the loaded project config is out-of-date
     */
    public function applyExternalChanges(): void
    {
        $this->acquireLock();

        try {
            DB::connection()->useWriteConnectionWhenReading();
            $this->reset();
            Cache::forget(self::CACHE_KEY);
            $incoming = $this->get(null, true);
            $changed = array_filter($this->getPendingChanges($incoming));
            $this->applyConfigChanges($incoming);

            if ($changed) {
                $this->storage->invalidate();
            }
        } finally {
            $this->releaseLock();
        }
    }

    /** @param array<string|int, mixed> $configData */
    public function applyConfigChanges(array $configData): void
    {
        $this->resetClaimedPaths();
        $this->processedPaths = [];
        $this->handlers->reset();
        $this->isApplyingExternalChanges = true;

        try {
            $changes = $this->getPendingChanges($configData);

            foreach (['removedItems', 'changedItems', 'newItems'] as $category) {
                foreach ($changes[$category] as $path) {
                    // A path can contain both changed and new values; new items still need their handlers.
                    $this->commit($path, ProjectConfigHelper::traverseDataArray($configData, $path), triggerUpdate: $category === 'newItems');
                }
            }

            $this->handlers->runDeferred($this->maxDefers);
            event(new ChangesApplied);
            $this->updateParsedConfigTimesAfterRequest();
        } catch (Throwable $exception) {
            $this->releaseLock();

            throw $exception;
        } finally {
            $this->isApplyingExternalChanges = false;
        }
    }

    /**
     * Processes changes in the project config files for a given config item path.
     *
     * Note that this will only have an effect if external project config changes are currently getting [[isApplyingExternalChanges()|applied]].
     *
     * @param  string  $path  The config item path
     * @param  bool  $force  Whether the config change should be processed regardless of previous records,
     *                       or whether external changes are currently being applied
     */
    public function processConfigChanges(string $path, bool $force = false): void
    {
        if ((! $this->isApplyingExternalChanges && ! $force) || (! $force && isset($this->processedPaths[$path]))) {
            return;
        }

        $value = $this->get($path, true);
        $this->commit($path, $value, force: $force, previousConfig: $this->original);
    }

    /**
     * Rebuilds the project config from the current state in the database.
     *
     * @throws BusyResourceException if a lock could not be acquired
     * @throws StaleResourceException if the loaded project config is out-of-date
     * @throws Throwable if rebuilding or a rebuild listener fails
     */
    public function rebuild(): void
    {
        $this->acquireLock();
        $readOnly = $this->readOnly;
        $muteEvents = $this->muteEvents;

        try {
            $this->reset();
            $this->readOnly = false;
            $this->muteEvents = true;
            $rebuilt = new ProjectConfigRebuilt(app(ConfigRebuilder::class)->build($this->get()));
            event($rebuilt);
            $this->commit(self::PATH_META_NAMES, null);

            foreach ($rebuilt->config as $path => $value) {
                $this->set($path, $value, 'Project config rebuild', updateTimestamp: false, force: true);
            }

            $this->storage->invalidate();

            if ($this->writeYamlAutomatically) {
                $this->writeYamlFiles(true);
            }
        } finally {
            $this->readOnly = $readOnly;
            $this->muteEvents = $muteEvents;
            $this->releaseLock();
        }
    }

    /**
     * Returns whether all schema versions stored in the config match the current codebase.
     * The schemas must match exactly to avoid applying project config changes while migrations are pending.
     *
     * @param  list<array{cause: string, existing: string, incoming: mixed}>  $issues  Populated with incompatible schema versions.
     */
    public function getAreConfigSchemaVersionsCompatible(array &$issues = []): bool
    {
        $incoming = $this->get(self::PATH_SCHEMA_VERSION, true);

        if (version_compare(Cms::SCHEMA_VERSION, (string) $incoming, '!=')) {
            $issues[] = ['cause' => 'Craft CMS', 'existing' => Cms::SCHEMA_VERSION, 'incoming' => $incoming];
        }

        foreach (app(Plugins::class)->getAllPlugins() as $plugin) {
            $incoming = $this->get(self::PATH_PLUGINS.'.'.$plugin->handle.'.schemaVersion', true);

            if ($incoming !== null && version_compare($plugin->schemaVersion, (string) $incoming, '!=')) {
                $issues[] = ['cause' => $plugin->name ?? $plugin->handle, 'existing' => $plugin->schemaVersion, 'incoming' => $incoming];
            }
        }

        return $issues === [];
    }
}
