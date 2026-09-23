<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Import\Data\ImportPlan as ImportPlanData;
use CraftCms\Cms\Import\Events\ImportPlanSaved;
use CraftCms\Cms\Import\Events\ImportPlanSaving;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Import\Models\ImportPlan as ImportPlanModel;
use CraftCms\Cms\Support\Facades\ImportLog;
use CraftCms\Cms\Support\Str;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection as LaravelCollection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Singleton]
class ImportPlan
{
    /**
     * @param  LaravelCollection|null  $imports  The cached collection of import plans.
     */
    public function __construct(
        private ?LaravelCollection $imports = null,
    ) {}

    /**
     * Instantiates an importer from an import plan step, applying its properties and decoded
     * settings via setter methods.
     *
     * @param  array  $step  The step array, shaped `{uid, type, file, transformer, settings}`.
     */
    public static function createImporter(array $step): ?BaseImporter
    {
        try {
            return new $step['type']($step);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Lazily loads/caches all import plans, merging DB-stored import plans with the file-based
     * `craft.import` config, keyed by handle and sorted by name.
     */
    public function getAllImportPlans(): LaravelCollection
    {
        if ($this->imports === null) {
            $dbImports = array_map(
                fn ($row) => new ImportPlanData((array) $row + ['editable' => true]),
                $this->_importPlanQuery()->get()->all(),
            );

            $fileImports = array_filter(array_map(function ($fileImport) {
                $fileImport = $fileImport();

                if (! $fileImport->validate()) {
                    ImportLog::warning("Skipping invalid file-based import plan \"{$fileImport->handle}\": ".implode(' ', $fileImport->errors()->all()));

                    return null;
                }

                return $fileImport;
            }, Config::get('craft.import', [])));

            $this->imports = new LaravelCollection($dbImports + $fileImports)
                ->keyBy(fn (ImportPlanData $item, $key) => $item->handle ?? $key)
                ->sortBy('name');
        }

        return $this->imports;
    }

    /**
     * Filters all import plans down to editable (DB-backed) ones.
     */
    public function getEditableImportPlans(): LaravelCollection
    {
        return $this->getAllImportPlans()->filter(fn (ImportPlanData $importPlan) => $importPlan->isEditable());
    }

    /**
     * Filters all import plans down to non-editable (file-based) ones.
     */
    public function getNonEditableImportPlans(): LaravelCollection
    {
        return $this->getAllImportPlans()->reject(fn (ImportPlanData $importPlan) => $importPlan->isEditable());
    }

    /**
     * Looks up an import plan by handle, optionally restricted to editable import plans.
     *
     * Falls back to querying the DB directly while the cache is still being built, so that
     * validating a file-based import plan — which checks its handle against the saved ones —
     * doesn't re-enter `getAllImportPlans()`.
     *
     * @param  string|null  $handle  The import plan handle to look up.
     * @param  bool  $editableOnly  Whether to restrict the lookup to editable import plans.
     */
    public function getImportPlanByHandle(?string $handle, bool $editableOnly = false): ?ImportPlanData
    {
        if (is_null($handle)) {
            return null;
        }

        if ($this->imports !== null) {
            /** @var ImportPlanData|null */
            return ($editableOnly ? $this->getEditableImportPlans() : $this->getAllImportPlans())
                ->where('handle', $handle)
                ->first();
        }

        $row = $this->_importPlanQuery()->where('handle', $handle)->first();
        if ($row !== null) {
            return new ImportPlanData((array) $row + ['editable' => true]);
        }

        if ($editableOnly) {
            return null;
        }

        /** @var ImportPlanData|null */
        return $this->getNonEditableImportPlans()->where('handle', $handle)->first();
    }

    /**
     * Looks up an import plan by UID, optionally restricted to editable import plans.
     *
     * @param  string  $uid  The UID of the import plan to look up.
     * @param  bool  $editableOnly  Whether to restrict the lookup to editable import plans.
     */
    public function getImportPlanByUid(string $uid, bool $editableOnly = false): ?ImportPlanData
    {
        if ($this->imports !== null) {
            /** @var ImportPlanData|null */
            return ($editableOnly ? $this->getEditableImportPlans() : $this->getAllImportPlans())
                ->where('uid', $uid)
                ->first();
        }

        $row = $this->_importPlanQuery()->where('uid', $uid)->first();
        if ($row !== null) {
            return new ImportPlanData((array) $row + ['editable' => true]);
        }

        if ($editableOnly) {
            return null;
        }

        /** @var ImportPlanData|null */
        return $this->getAllImportPlans()->where('uid', $uid)->first();
    }

    /**
     * Fires a saving event, validates the import plan, persists it to the import plans table inside a
     * transaction, invalidates the import plans cache, then fires a saved event.
     *
     * @param  ImportPlanData  $importPlan  The import plan to save.
     */
    public function saveImportPlan(ImportPlanData $importPlan): bool
    {
        $isNewImport = ! $importPlan->uid;

        event($event = new ImportPlanSaving($importPlan, $isNewImport));

        if (! $event->isValid) {
            return false;
        }

        $importPlan = $event->importPlan;

        if (! $importPlan->validate()) {
            return false;
        }

        if ($isNewImport) {
            $importPlan->uid = Str::uuid7()->toString();
        }

        $importRecord = $this->_getImportPlanModel($importPlan->uid);

        DB::beginTransaction();

        try {
            $importRecord->uid = $importPlan->uid;
            $importRecord->name = $importPlan->name;
            $importRecord->handle = $importPlan->handle;
            $importRecord->description = $importPlan->description;
            $importRecord->steps = $importPlan->serializeSteps();

            $importRecord->save();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        // invalidate caches
        $this->imports = null;

        event(new ImportPlanSaved($importPlan, $isNewImport));

        return true;
    }

    /**
     * Duplicates an editable import plan, giving the copy a fresh handle and step UIDs.
     */
    public function duplicateImportPlan(ImportPlanData $importPlan): void
    {
        $importRecord = $this->_getImportPlanModel($importPlan->uid);

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
            if (! $this->getImportPlanByHandle($testHandle)) {
                $newImport->handle = $testHandle;
                break;
            }
        } while (true);

        $newImport->save();

        // invalidate caches
        $this->imports = null;
    }

    /**
     * Soft-deletes the DB record for an import plan and invalidates the import plans cache.
     *
     * @param  ImportPlanData  $importPlan  The import plan to delete.
     */
    public function deleteImportPlan(ImportPlanData $importPlan): void
    {
        $importRecord = $this->_getImportPlanModel($importPlan->uid);

        if (! $importRecord->exists) {
            return;
        }

        $importRecord->delete();

        // invalidate caches
        $this->imports = null;
    }

    /**
     * Returns an import plan model for a given UID
     */
    private function _getImportPlanModel(string $uid, bool $withTrashed = false): ImportPlanModel
    {
        return ImportPlanModel::withTrashed($withTrashed)
            ->where('uid', $uid)
            ->first() ?? new ImportPlanModel;
    }

    /**
     * Builds the base query for selecting non-deleted rows from the import plans table.
     */
    private function _importPlanQuery(): Builder
    {
        return DB::table(Table::IMPORT_PLANS)
            ->select([
                'name',
                'handle',
                'description',
                'steps',
                'import_plans.uid',
            ])
            ->orderBy('name')
            ->orderBy('handle')
            ->whereNull('dateDeleted');
    }
}
