<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Import\Events\ImportConfigSaved;
use CraftCms\Cms\Import\Events\ImportConfigSaving;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Import\Models\ImportConfig as ImportConfigModel;
use CraftCms\Cms\Support\Json as JsonSupport;
use CraftCms\Cms\Support\Str;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection as LaravelCollection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Singleton]
class ImportConfig
{
    /**
     * @param  LaravelCollection|null  $configs  The cached collection of importer configs.
     */
    public function __construct(
        // private readonly ProjectConfig $projectConfig,
        private ?LaravelCollection $configs = null,
    ) {}

    /**
     * Instantiates an importer from a config array, applying properties and decoded settings via setter methods.
     *
     * @param  array  $config  The importer config array.
     */
    public function createImporter(array $config): BaseImporter
    {
        $importer = new $config['type']($config);
        $importer->name($config['name']);
        $importer->handle($config['handle']);
        $importer->description($config['description']);
        $settings = JsonSupport::decode($config['settings']);
        foreach ($settings as $setting => $value) {
            if (method_exists($importer, $setting)) {
                $importer->{$setting}($value);
            }
        }

        return $importer;
    }

    /**
     * Lazily loads/caches all importer configs, merging DB-stored configs with file-based `craft.import` config,
     * keyed by handle and sorted by name.
     * Returns the collection of importer configs, keyed by handle.
     */
    public function getAllConfigs(): LaravelCollection
    {
        if ($this->configs === null) {
            $dbConfigs = $this->_importConfigQuery()->get()->all();
            $dbConfigs = array_map(
                fn ($config) => $this->createImporter((array) $config + ['editable' => true]),
                $dbConfigs
            );

            $fileConfigs = Config::get('craft.import') ?? [];

            foreach ($fileConfigs as &$fileConfig) {
                $fileConfig = $fileConfig();

                // if there's no transformer set, use the default one
                if ($fileConfig->transformer === null) {
                    $fileConfig->transformer(null);
                }
            }

            $this->configs = new LaravelCollection($dbConfigs + $fileConfigs)
                ->keyBy(fn (BaseImporter $item, $key) => $item->handle ?? $key)
                ->sortBy('name');
        }

        return $this->configs;
    }

    /**
     * Filters all configs down to editable (DB-backed) ones.
     */
    public function getEditableConfigs(): LaravelCollection
    {
        return $this->getAllConfigs()->filter(fn ($config) => $config->isEditable());
    }

    /**
     * Filters all configs down to non-editable (file-based) ones.
     */
    public function getNonEditableConfigs(): LaravelCollection
    {
        return $this->getAllConfigs()->reject(fn ($config) => $config->isEditable());
    }

    /**
     * Looks up an importer config by handle, optionally restricted to editable configs.
     *
     * @param  string|null  $handle  The importer config handle to look up.
     * @param  bool  $editableOnly  Whether to restrict the lookup to editable configs.
     */
    public function getConfigByHandle(?string $handle, bool $editableOnly = false): ?BaseImporter
    {
        if (is_null($handle)) {
            return null;
        }

        if ($editableOnly) {
            $configs = $this->getEditableConfigs();
        } else {
            $configs = $this->getAllConfigs();
        }

        /** @var BaseImporter|null */
        return $configs->where('handle', $handle)->first();
    }

    /**
     * Looks up an importer config by UID, optionally restricted to editable configs.
     *
     * @param  string  $uid  The UID of the importer config to look up.
     * @param  bool  $editableOnly  Whether to restrict the lookup to editable configs.
     */
    public function getConfigByUid(string $uid, bool $editableOnly = false): ?BaseImporter
    {
        if ($editableOnly) {
            $configs = $this->getEditableConfigs();
        } else {
            $configs = $this->getAllConfigs();
        }

        /** @var BaseImporter|null */
        return $configs->where('uid', $uid)->first();
    }

    /**
     * Fires a saving event, persists importer settings to the import_configs table inside a transaction,
     * invalidates the configs cache, then fires a saved event.
     *
     * @param  BaseImporter  $importer  The importer config to save.
     */
    public function saveConfig(BaseImporter $importer): bool
    {
        $isNewConfig = ! $importer->uid;

        event($event = new ImportConfigSaving($importer, $isNewConfig));

        if (! $event->isValid) {
            return false;
        }

        $importer = $event->importer;

        if ($isNewConfig) {
            $importer->uid = Str::uuid7()->toString();
        }

        $configRecord = $this->_getImportConfigModel($importer->uid);

        DB::beginTransaction();

        try {
            $configRecord->uid = $importer->uid;
            $configRecord->type = $importer::class;
            $configRecord->name = $importer->name;
            $configRecord->handle = $importer->handle;
            $configRecord->description = $importer->description;
            $settings = [
                'file' => $importer->file,
                'className' => $importer->className,
                'transformer' => $importer->transformer ? $importer->transformer::class : null,
                'map' => $importer->map,
                'matchCriteria' => $importer->matchCriteria,
                'clearableItems' => $importer->clearableItems,
                'keepMissingNestedElements' => $importer->keepMissingNestedElements,
            ];
            if (property_exists($importer, 'site')) {
                $settings['site'] = $importer->site->uid;
            }
            if (property_exists($importer, 'fieldLayout')) {
                $settings['fieldLayout'] = $importer->fieldLayout;
            }
            $configRecord->settings = $settings;
            $configRecord->save();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        // invalidate caches
        $this->configs = null;

        event(new ImportConfigSaved($importer, $isNewConfig));

        return true;
    }

    /**
     * Duplicate editable config.
     */
    public function duplicateConfig(BaseImporter $importer): void
    {
        $configRecord = $this->_getImportConfigModel($importer->uid);

        // if we couldn't find it - return
        if (! $configRecord->exists) {
            return;
        }

        $newConfig = $configRecord->replicate();
        $newConfig->uid = Str::uuid7()->toString();

        if (preg_match('/^(.*?)(\d+)$/', (string) $newConfig->handle, $match)) {
            $baseHandle = $match[1];
            $i = (int) $match[2];
        } else {
            $baseHandle = $newConfig->handle;
            $i = 1;
        }
        do {
            $testHandle = sprintf('%s%s', $baseHandle, ++$i);
            if (! $this->getConfigByHandle($testHandle)) {
                $newConfig->handle = $testHandle;
                break;
            }
        } while (true);

        $newConfig->save();

        // invalidate caches
        $this->configs = null;
    }

    /**
     * Soft-deletes the DB record for an importer config and invalidates the configs cache.
     *
     * @param  BaseImporter  $importer  The importer config to delete.
     */
    public function deleteConfig(BaseImporter $importer): void
    {
        $configRecord = $this->_getImportConfigModel($importer->uid);

        if (! $configRecord->exists) {
            return;
        }

        $configRecord->delete();

        // invalidate caches
        $this->configs = null;
    }

    /**
     * Returns an import config model for a given UID
     */
    private function _getImportConfigModel(string $uid, bool $withTrashed = false): ImportConfigModel
    {
        return ImportConfigModel::withTrashed($withTrashed)
            ->where('uid', $uid)
            ->first() ?? new ImportConfigModel;
    }

    /**
     * Builds the base query for selecting non-deleted rows from the import_configs table.
     */
    private function _importConfigQuery(): Builder
    {
        return DB::table(Table::IMPORT_CONFIGS)
            ->select([
                'import_configs.type',
                'import_configs.name',
                'import_configs.handle',
                'import_configs.description',
                'import_configs.settings',
                'import_configs.uid',
            ])
            ->orderBy('import_configs.name')
            ->orderBy('import_configs.handle')
            ->whereNull('import_configs.dateDeleted');
    }
}
