<?php

declare(strict_types=1);

namespace CraftCms\Cms\Update;

use Craft;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Exceptions\MigrateException;
use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\License\License;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Shared\Models\Info;
use CraftCms\Cms\Support\Api;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Update\Data\Updates as UpdatesData;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDOException;
use Throwable;

/**
 * @internal
 */
#[Singleton]
class Updates
{
    private ?UpdatesData $updates = null;

    /** @see isCraftUpdatePending() */
    private ?bool $isCraftUpdatePending = null;

    public function __construct(
        private readonly Api $api,
        private readonly Migrator $migrator,
        private readonly Plugins $plugins,
        private readonly ProjectConfig $projectConfig,
    ) {}

    public function isUpdateInfoCached(): bool
    {
        return isset($this->updates) || Cache::has(self::class);
    }

    /**
     * @param  bool  $check  Whether to check for updates if they aren't cached already
     */
    public function totalAvailableUpdates(bool $check = false): int
    {
        if (! $check && ! $this->isUpdateInfoCached()) {
            return 0;
        }

        return $this->getUpdates()->getTotal();
    }

    /**
     * Returns whether a critical update is available.
     *
     * @param  bool  $check  Whether to check for updates if they aren't cached already
     */
    public function isCriticalUpdateAvailable(bool $check = false): bool
    {
        if (! $check && ! $this->isUpdateInfoCached()) {
            return false;
        }

        return $this->getUpdates()->hasCritical();
    }

    public function getUpdates(bool $refresh = false): UpdatesData
    {
        if (! $refresh) {
            if (isset($this->updates)) {
                return $this->updates;
            }

            if ($cached = Cache::get(self::class)) {
                return $this->updates = $cached;
            }
        }

        try {
            $updatesData = UpdatesData::fromArray($this->api->getUpdates());
        } catch (Throwable $e) {
            Log::warning("Couldn't get updates: {$e->getMessage()}", [__METHOD__]);

            $updatesData = new UpdatesData;
        }

        return $this->cacheUpdates($updatesData);
    }

    public function cacheUpdates(UpdatesData $updatesData): UpdatesData
    {
        // Instantiate the Updates model first so we don't chance caching bad data
        // Cache for 5 minutes or 24 hours depending on whether we actually have results
        $cacheDuration = $updatesData->getTotal() > 0 ? now()->addDay() : now()->addMinutes(5);

        Cache::put(self::class, $updatesData, $cacheDuration);

        return $this->updates = $updatesData;
    }

    /**
     * Returns whether there are any pending migrations.
     *
     * @param  bool  $includeContent  Whether pending content migrations should be considered
     */
    public function areMigrationsPending(bool $includeContent = false): bool
    {
        if ($this->isCraftUpdatePending()) {
            return true;
        }

        if (array_any(
            $this->plugins->getAllPlugins(),
            $this->plugins->isPluginUpdatePending(...)
        )) {
            return true;
        }

        if ($includeContent && ! empty($this->migrator->track('content')->getPendingMigrations())) {
            return true;
        }

        return false;
    }

    /**
     * Returns a list of things with updated schema versions.
     *
     * Craft CMS will be represented as "craft", plugins will be represented by their handles, and content will be represented as "content".
     *
     * @param  bool  $includeContent  Whether pending content migrations should be considered
     * @return string[]
     *
     * @see runMigrations()
     */
    public function pendingMigrationHandles(bool $includeContent = false): array
    {
        $handles = [];

        if ($this->isCraftUpdatePending()) {
            $handles[] = 'craft';
        }

        foreach ($this->plugins->getAllPlugins() as $plugin) {
            if ($this->plugins->isPluginUpdatePending($plugin)) {
                $handles[] = $plugin->handle;
            }
        }

        if ($includeContent && ! empty($this->migrator->track('content')->getPendingMigrations())) {
            $handles[] = 'content';
        }

        return $handles;
    }

    /**
     * Runs the pending migrations for the given list of handles.
     *
     * @param  string[]  $handles  The list of handles to run migrations for
     *
     * @see getPendingMigrationHandles()
     */
    public function runMigrations(array $handles): void
    {
        // Make sure Craft is first
        if (Arr::pull($handles, 'craft') !== null) {
            array_unshift($handles, 'craft');
        }

        // Make sure content is last
        if (Arr::pull($handles, 'content') !== null) {
            $handles[] = 'content';
        }

        DB::beginTransaction();

        try {
            foreach ($handles as $handle) {
                if ($handle === 'craft') {
                    $this->migrator->track('craft')->run();

                    $this->updateCraftVersionInfo();
                } elseif ($handle === 'content') {
                    $this->migrator->track('content')->run();
                } else {
                    if (! $plugin = $this->plugins->getPlugin($handle)) {
                        continue;
                    }

                    $name = $plugin->name;
                    $plugin->getMigrator()->run();
                    $this->plugins->updatePluginVersionInfo($plugin);
                }
            }
        } catch (Throwable $e) {
            DB::rollBack();
            throw new MigrateException($name ?? 'Craft', $handle, null, 0, $e);
        }

        try {
            DB::commit();
        } catch (PDOException $e) {
            // The transaction could be implicitly committed by Mysql
            if ($e->getMessage() !== 'There is no active transaction') {
                throw $e;
            }
        }

        // Delete all compiled templates
        try {
            File::cleanDirectory(Path::compiledTemplates(create: false));
        } catch (Throwable $e) {
            Log::error('Could not delete compiled templates: '.$e->getMessage());

            report($e);
        }
    }

    /**
     * Returns whether any Craft or plugin updates are pending.
     */
    public function isUpdatePending(): bool
    {
        if ($this->isCraftUpdatePending()) {
            return true;
        }

        return $this->isPluginUpdatePending();
    }

    /**
     * Returns whether a plugin needs to run a database update.
     */
    public function isPluginUpdatePending(): bool
    {
        return array_any(
            $this->plugins->getAllPlugins(),
            $this->plugins->isPluginUpdatePending(...)
        );
    }

    /**
     * Returns whether a different Craft version has been uploaded.
     */
    public function hasCraftVersionChanged(): bool
    {
        return Info::fetch()->version != Cms::VERSION;
    }

    /**
     * Returns true if the version stored in craft_info is less than the minimum required version on the file system.
     * This effectively makes sure that a user cannot manually update past a manual breakpoint.
     */
    public function wasCraftBreakpointSkipped(): bool
    {
        return version_compare(Cms::VERSION, Info::fetch()->version, '>');
    }

    /**
     * Returns whether the uploaded DB schema is equal to or greater than the installed schema.
     */
    public function isCraftSchemaVersionCompatible(): bool
    {
        $storedSchemaVersion = Info::fetch()->schemaVersion;

        return version_compare(Cms::SCHEMA_VERSION, $storedSchemaVersion, '>=');
    }

    /**
     * Returns whether Craft needs to run any database migrations.
     */
    public function isCraftUpdatePending(): bool
    {
        if (! isset($this->isCraftUpdatePending)) {
            $storedSchemaVersion = Info::fetch()->schemaVersion;
            $this->isCraftUpdatePending = version_compare(Cms::SCHEMA_VERSION, $storedSchemaVersion, '>');
        }

        return $this->isCraftUpdatePending;
    }

    /**
     * Updates the Craft version info in the craft_info table.
     */
    public function updateCraftVersionInfo(): bool
    {
        $info = Info::fetch();
        $info->update([
            'version' => Cms::VERSION,
            'schemaVersion' => Cms::SCHEMA_VERSION,
        ]);

        $this->projectConfig->set(
            ProjectConfig::PATH_SCHEMA_VERSION,
            $info->schemaVersion,
            'Update Craft schema version',
        );

        $this->isCraftUpdatePending = null;

        // Clear the license info cache
        Cache::forget(License::CACHE_KEY_LICENSE_INFO);

        return true;
    }
}
