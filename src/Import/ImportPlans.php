<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import;

use Closure;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Import\Data\ImportPlan as ImportPlanData;
use CraftCms\Cms\Import\Events\ImportPlanSaved;
use CraftCms\Cms\Import\Events\ImportPlanSaving;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Import\Models\ImportPlan as ImportPlanModel;
use CraftCms\Cms\Support\Facades\ImportLog;
use CraftCms\Cms\Support\Str;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection as LaravelCollection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Manages import plans: editable ones stored in the database, and non-editable ones registered in code.
 *
 * Non-editable import plans are registered as factories, which aren't called until the import plans
 * are first needed.
 *
 * ```php
 * public function boot(ImportPlans $importPlans): void
 * {
 *     $importPlans->register('entriesFromFile', fn () => new ImportPlanData()
 *         ->name('Entries from file')
 *         ->steps([
 *             new EntryImporter()
 *                 ->source('resources/import/entries.json')
 *                 ->section('news'),
 *         ]));
 * }
 * ```
 *
 * @since 6.0.0
 */
#[Singleton]
class ImportPlans
{
    /** @var array<string, Closure> */
    private array $registeredImportPlans = [];

    /**
     * @param  LaravelCollection<array-key, ImportPlanData>|null  $importPlans  The cached collection of import plans.
     */
    public function __construct(
        private readonly Container $container,
        private ?LaravelCollection $importPlans = null,
    ) {}

    /**
     * Registers a factory for a non-editable import plan. The factory must return an import plan
     * whose handle is either unset or matches the registered handle.
     *
     * @param  string  $handle  The import plan's handle.
     * @param  Closure  $factory  The factory that creates the import plan.
     */
    public function register(string $handle, Closure $factory): void
    {
        if ($handle === '') {
            throw new InvalidArgumentException('Import plan handles cannot be empty.');
        }

        if (isset($this->registeredImportPlans[$handle])) {
            throw new InvalidArgumentException("Import plan [$handle] is already registered.");
        }

        $this->registeredImportPlans[$handle] = $factory;
        $this->importPlans = null;
    }

    /**
     * Removes registered non-editable import plans.
     *
     * @param  string  ...$handles  The handles of the import plans to remove.
     */
    public function remove(string ...$handles): void
    {
        foreach ($handles as $handle) {
            unset($this->registeredImportPlans[$handle]);
        }

        $this->importPlans = null;
    }

    /**
     * Instantiates an importer from an import plan step, applying its properties and decoded
     * settings via setter methods. Returns null for a step whose type isn't an importer, or whose
     * importer can't be built (which is logged).
     *
     * @param  array<string, mixed>  $step  The step array, shaped `{uid, type, source, transformer, settings}`.
     */
    public static function createImporter(array $step): ?BaseImporter
    {
        $type = $step['type'] ?? null;

        if (! is_string($type) || ! is_subclass_of($type, BaseImporter::class)) {
            return null;
        }

        try {
            return new $type($step);
        } catch (Throwable $e) {
            ImportLog::warning("Couldn’t create the “{$type}” importer for step “".($step['uid'] ?? '?')."”: {$e->getMessage()}", [
                'uid' => $step['uid'] ?? null,
                'type' => $type,
                'exception' => $e,
            ]);

            return null;
        }
    }

    /**
     * Lazily loads/caches all import plans, merging DB-stored import plans, in their saved order,
     * with the registered ones, sorted by name, keyed by handle.
     *
     * @return LaravelCollection<array-key, ImportPlanData>
     */
    public function getAllImportPlans(): LaravelCollection
    {
        if ($this->importPlans === null) {
            $dbImportPlans = array_map(
                fn ($row) => new ImportPlanData((array) $row + ['editable' => true]),
                $this->_importPlanQuery()->get()->all(),
            );

            $registeredImportPlans = [];

            foreach ($this->registeredImportPlans as $handle => $factory) {
                $importPlan = $this->resolveRegisteredImportPlan($handle, $factory);

                if ($importPlan !== null) {
                    $registeredImportPlans[$handle] = $importPlan;
                }
            }

            $registeredImportPlans = new LaravelCollection($registeredImportPlans)->all();

            $this->importPlans = new LaravelCollection($dbImportPlans + $registeredImportPlans)
                ->keyBy(fn (ImportPlanData $item, $key) => $item->handle ?? $key);
        }

        return $this->importPlans;
    }

    /**
     * Calls a registered import plan's factory and validates the result. Returns null, and logs a
     * warning, if the import plan is invalid or its handle doesn't match the registered one.
     */
    private function resolveRegisteredImportPlan(string $handle, Closure $factory): ?ImportPlanData
    {
        $importPlan = $this->container->call($factory);

        if (! $importPlan instanceof ImportPlanData) {
            ImportLog::warning("Skipping registered import plan \"$handle\": its factory must return an import plan.");

            return null;
        }

        $importPlan->handle ??= $handle;

        if ($importPlan->handle !== $handle) {
            ImportLog::warning("Skipping registered import plan \"$handle\": its handle doesn’t match the registered one (\"{$importPlan->handle}\").");

            return null;
        }

        if (! $importPlan->validate()) {
            ImportLog::warning("Skipping invalid registered import plan \"$handle\": ".implode(' ', $importPlan->errors()->all()));

            return null;
        }

        return $importPlan;
    }

    /**
     * Filters all import plans down to editable (DB-backed) ones.
     *
     * @return LaravelCollection<array-key, ImportPlanData>
     */
    public function getEditableImportPlans(): LaravelCollection
    {
        return $this->getAllImportPlans()->filter(fn (ImportPlanData $importPlan) => $importPlan->isEditable());
    }

    /**
     * Filters all import plans down to non-editable (registered) ones.
     *
     * @return LaravelCollection<array-key, ImportPlanData>
     */
    public function getNonEditableImportPlans(): LaravelCollection
    {
        return $this->getAllImportPlans()->reject(fn (ImportPlanData $importPlan) => $importPlan->isEditable());
    }

    /**
     * Looks up an import plan by handle, optionally restricted to editable import plans.
     *
     * Falls back to querying the DB directly while the cache is still being built, so that
     * validating a registered import plan — which checks its handle against the saved ones —
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

        if ($this->importPlans !== null) {
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
        if ($this->importPlans !== null) {
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
     * Looks up an import plan by UID if the given value is one, otherwise by handle.
     *
     * @param  string  $handleOrUid  The handle or UID of the import plan to look up.
     */
    public function getImportPlanByHandleOrUid(string $handleOrUid): ?ImportPlanData
    {
        if (Str::isUuid($handleOrUid)) {
            return $this->getImportPlanByUid($handleOrUid);
        }

        return $this->getImportPlanByHandle($handleOrUid);
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

            if (! $importRecord->exists) {
                $importRecord->sortOrder = $this->nextSortOrder();
            }

            $importRecord->save();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        // invalidate caches
        $this->importPlans = null;

        event(new ImportPlanSaved($importPlan, $isNewImport));

        return true;
    }

    /**
     * Duplicates an editable import plan, giving the copy a fresh handle and step UIDs, and saves it
     * the same way as any other import plan.
     */
    public function duplicateImportPlan(ImportPlanData $importPlan): bool
    {
        if (! $importPlan->isEditable() || ! $this->_getImportPlanModel($importPlan->uid)->exists) {
            return false;
        }

        $copy = new ImportPlanData([
            'name' => $importPlan->name,
            'handle' => $this->uniqueHandle((string) $importPlan->handle),
            'description' => $importPlan->description,
            'editable' => true,
            // dropping the uids gives the copy's steps new ones
            'steps' => array_map(
                fn (BaseImporter $step): array => ['uid' => null] + $step->toArrayData(),
                $importPlan->steps ?? [],
            ),
        ]);

        return $this->saveImportPlan($copy);
    }

    /**
     * Returns the given handle with an incremented numeric suffix that no other import plan uses.
     */
    private function uniqueHandle(string $handle): string
    {
        if (preg_match('/^(.*?)(\d+)$/', $handle, $match)) {
            $baseHandle = $match[1];
            $i = (int) $match[2];
        } else {
            $baseHandle = $handle;
            $i = 1;
        }

        do {
            $testHandle = sprintf('%s%s', $baseHandle, ++$i);
        } while ($this->getImportPlanByHandle($testHandle));

        return $testHandle;
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
        $this->importPlans = null;
    }

    /**
     * Saves the order of the editable import plans and invalidates the import plans cache.
     *
     * @param  string[]  $uids  The import plan UIDs, in their new order.
     */
    public function reorderImportPlans(array $uids): void
    {
        DB::transaction(function () use ($uids) {
            foreach (array_values($uids) as $index => $uid) {
                DB::table(Table::IMPORT_PLANS)
                    ->where('uid', $uid)
                    ->update(['sortOrder' => $index + 1]);
            }
        });

        // invalidate caches
        $this->importPlans = null;
    }

    /**
     * Returns the sort order that places an import plan after all the others.
     */
    private function nextSortOrder(): int
    {
        return (int) DB::table(Table::IMPORT_PLANS)->max('sortOrder') + 1;
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
            ->orderBy('sortOrder')
            ->orderBy('name')
            ->orderBy('handle')
            ->whereNull('dateDeleted');
    }
}
