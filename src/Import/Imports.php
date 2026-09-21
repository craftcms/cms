<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\Import\Data\Import as ImportData;
use CraftCms\Cms\Import\Events\ImportSaved;
use CraftCms\Cms\Import\Events\ImportSaving;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Import\Models\Import as ImportModel;
use CraftCms\Cms\Support\Facades\ImportLog;
use CraftCms\Cms\Support\Json as JsonSupport;
use CraftCms\Cms\Support\Str;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection as LaravelCollection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Throwable;

#[Singleton]
class Imports
{
    /**
     * @param  LaravelCollection|null  $imports  The cached collection of imports.
     */
    public function __construct(
        private ?LaravelCollection $imports = null,
    ) {}

    /**
     * Instantiates an importer from an import step, applying its properties and decoded
     * settings via setter methods.
     *
     * @param  array  $step  The step array, shaped `{uid, type, file, transformer, settings}`.
     */
    public static function createImporter(array $step): BaseImporter
    {
        $importer = new $step['type']($step);
        $importer->file($step['file'] ?? null);
        $importer->transformer($step['transformer'] ?? null);

        $settings = $step['settings'] ?? [];
        if (is_string($settings)) {
            $settings = JsonSupport::decode($settings);
        }

        foreach ($settings as $setting => $value) {
            if (method_exists($importer, $setting)) {
                $reflection = new ReflectionMethod($importer, $setting);
                if ($reflection->isPublic()) {
                    $importer->{$setting}($value);
                }
            }
        }

        // an element type whose layout isn't chosen through a setting resolves it here, so a
        // step that has never been saved still knows what it's importing into
        if ($importer instanceof ElementImporter && $importer->fieldLayout === null) {
            $importer->resolveDefaultFieldLayout();
        }

        return $importer;
    }

    /**
     * Lazily loads/caches all imports, merging DB-stored imports with the file-based
     * `craft.import` config, keyed by handle and sorted by name.
     */
    public function getAllImports(): LaravelCollection
    {
        if ($this->imports === null) {
            $dbImports = array_map(
                fn ($row) => new ImportData((array) $row + ['editable' => true]),
                $this->_importQuery()->get()->all(),
            );

            $fileImports = array_filter(array_map(function ($fileImport) {
                $fileImport = $fileImport();

                if (! $fileImport->validate()) {
                    ImportLog::warning("Skipping invalid file-based import \"{$fileImport->handle}\": ".implode(' ', $fileImport->errors()->all()));

                    return null;
                }

                return $fileImport;
            }, Config::get('craft.import', [])));

            $this->imports = new LaravelCollection($dbImports + $fileImports)
                ->keyBy(fn (ImportData $item, $key) => $item->handle ?? $key)
                ->sortBy('name');
        }

        return $this->imports;
    }

    /**
     * Filters all imports down to editable (DB-backed) ones.
     */
    public function getEditableImports(): LaravelCollection
    {
        return $this->getAllImports()->filter(fn (ImportData $import) => $import->isEditable());
    }

    /**
     * Filters all imports down to non-editable (file-based) ones.
     */
    public function getNonEditableImports(): LaravelCollection
    {
        return $this->getAllImports()->reject(fn (ImportData $import) => $import->isEditable());
    }

    /**
     * Looks up an import by handle, optionally restricted to editable imports.
     *
     * Falls back to querying the DB directly while the cache is still being built, so that
     * validating a file-based import — which checks its handle against the saved ones —
     * doesn't re-enter `getAllImports()`.
     *
     * @param  string|null  $handle  The import handle to look up.
     * @param  bool  $editableOnly  Whether to restrict the lookup to editable imports.
     */
    public function getImportByHandle(?string $handle, bool $editableOnly = false): ?ImportData
    {
        if (is_null($handle)) {
            return null;
        }

        if ($this->imports !== null) {
            /** @var ImportData|null */
            return ($editableOnly ? $this->getEditableImports() : $this->getAllImports())
                ->where('handle', $handle)
                ->first();
        }

        $row = $this->_importQuery()->where('handle', $handle)->first();
        if ($row !== null) {
            return new ImportData((array) $row + ['editable' => true]);
        }

        if ($editableOnly) {
            return null;
        }

        /** @var ImportData|null */
        return $this->getAllImports()->where('handle', $handle)->first();
    }

    /**
     * Looks up an import by UID, optionally restricted to editable imports.
     *
     * @param  string  $uid  The UID of the import to look up.
     * @param  bool  $editableOnly  Whether to restrict the lookup to editable imports.
     */
    public function getImportByUid(string $uid, bool $editableOnly = false): ?ImportData
    {
        if ($this->imports !== null) {
            /** @var ImportData|null */
            return ($editableOnly ? $this->getEditableImports() : $this->getAllImports())
                ->where('uid', $uid)
                ->first();
        }

        $row = $this->_importQuery()->where('uid', $uid)->first();
        if ($row !== null) {
            return new ImportData((array) $row + ['editable' => true]);
        }

        if ($editableOnly) {
            return null;
        }

        /** @var ImportData|null */
        return $this->getAllImports()->where('uid', $uid)->first();
    }

    /**
     * Fires a saving event, validates the import, persists it to the imports table inside a
     * transaction, invalidates the imports cache, then fires a saved event.
     *
     * @param  ImportData  $import  The import to save.
     */
    public function saveImport(ImportData $import): bool
    {
        $isNewImport = ! $import->uid;

        event($event = new ImportSaving($import, $isNewImport));

        if (! $event->isValid) {
            return false;
        }

        $import = $event->import;

        if (! $import->validate()) {
            return false;
        }

        if ($isNewImport) {
            $import->uid = Str::uuid7()->toString();
        }

        $importRecord = $this->_getImportModel($import->uid);

        DB::beginTransaction();

        try {
            $importRecord->uid = $import->uid;
            $importRecord->name = $import->name;
            $importRecord->handle = $import->handle;
            $importRecord->description = $import->description;
            $importRecord->steps = $import->steps;

            $importRecord->save();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        // invalidate caches
        $this->imports = null;

        event(new ImportSaved($import, $isNewImport));

        return true;
    }

    /**
     * Duplicates an editable import, giving the copy a fresh handle and step UIDs.
     */
    public function duplicateImport(ImportData $import): void
    {
        $importRecord = $this->_getImportModel($import->uid);

        // if we couldn't find it - return
        if (! $importRecord->exists) {
            return;
        }

        $newImport = $importRecord->replicate();
        $newImport->uid = Str::uuid7()->toString();
        $newImport->steps = array_map(function (array $step): array {
            $step['uid'] = Str::uuid7()->toString();

            return $step;
        }, $importRecord->steps ?? []);

        if (preg_match('/^(.*?)(\d+)$/', (string) $newImport->handle, $match)) {
            $baseHandle = $match[1];
            $i = (int) $match[2];
        } else {
            $baseHandle = $newImport->handle;
            $i = 1;
        }
        do {
            $testHandle = sprintf('%s%s', $baseHandle, ++$i);
            if (! $this->getImportByHandle($testHandle)) {
                $newImport->handle = $testHandle;
                break;
            }
        } while (true);

        $newImport->save();

        // invalidate caches
        $this->imports = null;
    }

    /**
     * Soft-deletes the DB record for an import and invalidates the imports cache.
     *
     * @param  ImportData  $import  The import to delete.
     */
    public function deleteImport(ImportData $import): void
    {
        $importRecord = $this->_getImportModel($import->uid);

        if (! $importRecord->exists) {
            return;
        }

        $importRecord->delete();

        // invalidate caches
        $this->imports = null;
    }

    /**
     * Returns an import model for a given UID
     */
    private function _getImportModel(string $uid, bool $withTrashed = false): ImportModel
    {
        return ImportModel::withTrashed($withTrashed)
            ->where('uid', $uid)
            ->first() ?? new ImportModel;
    }

    /**
     * Builds the base query for selecting non-deleted rows from the imports table.
     */
    private function _importQuery(): Builder
    {
        return DB::table(Table::IMPORTS)
            ->select([
                'name',
                'handle',
                'description',
                'steps',
                'imports.uid',
            ])
            ->orderBy('name')
            ->orderBy('handle')
            ->whereNull('dateDeleted');
    }
}
