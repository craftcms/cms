<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Import\Data\ImportRun as ImportRunData;
use CraftCms\Cms\Import\Events\ImportRunSaved;
use CraftCms\Cms\Import\Events\ImportRunSaving;
use CraftCms\Cms\Import\Models\ImportRun as ImportRunModel;
use CraftCms\Cms\Support\Str;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection as LaravelCollection;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Singleton]
class ImportRun
{
    /**
     * @param  LaravelCollection|null  $runs  The cached collection of import runs.
     */
    public function __construct(
        // private readonly ProjectConfig $projectConfig,
        private ?LaravelCollection $runs = null,
    ) {}

    /**
     * Lazily loads/caches all ImportRunData records, keyed by handle and sorted by name.
     * Returns the collection of import runs, keyed by handle.
     */
    public function getImportRuns(): LaravelCollection
    {
        if ($this->runs === null) {
            $runs = $this->_importRunQuery()->get()->all();
            $runs = array_map(fn ($run) => new ImportRunData($run), $runs);

            $this->runs = new LaravelCollection($runs)->keyBy('handle')->sortBy('name');
        }

        return $this->runs;
    }

    /**
     * Looks up an import run by handle.
     *
     * @param  string|null  $handle  The handle of the import run to look up.
     */
    public function getImportRunByHandle(?string $handle): ?ImportRunData
    {
        if (is_null($handle)) {
            return null;
        }

        /** @var ImportRunData|null */
        return $this->getImportRuns()->where('handle', $handle)->first();
    }

    /**
     * Looks up an import run by UID.
     *
     * @param  string  $uid  The UID of the import run to look up.
     */
    public function getImportRunByUid(string $uid): ?ImportRunData
    {
        /** @var ImportRunData|null */
        return $this->getImportRuns()->where('uid', $uid)->first();
    }

    /**
     * Fires a saving event, validates the run, persists it to import_runs in a transaction, invalidates the runs cache, then fires a saved event.
     *
     * @param  ImportRunData  $run  The import run to save.
     */
    public function saveRun(ImportRunData $run): bool
    {
        $isNewRun = ! $run->uid;

        event($event = new ImportRunSaving($run, $isNewRun));

        if (! $event->isValid) {
            return false;
        }

        $run = $event->run;

        if (! $run->validate()) {
            return false;
        }

        if ($isNewRun) {
            $run->uid = Str::uuid7()->toString();
        }

        $runRecord = $this->_getImportRunModel($run->uid);

        DB::beginTransaction();

        try {
            $runRecord->uid = $run->uid;
            $runRecord->name = $run->name;
            $runRecord->handle = $run->handle;
            $runRecord->description = $run->description;
            $runRecord->steps = $run->steps;

            $runRecord->save();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        // invalidate caches
        $this->runs = null;

        event(new ImportRunSaved($run, $isNewRun));

        return true;
    }

    /**
     * Soft-deletes the DB record for an import run and invalidates the runs cache.
     *
     * @param  ImportRunData  $run  The import run to delete.
     */
    public function deleteRun(ImportRunData $run): void
    {
        $runRecord = $this->_getImportRunModel($run->uid);

        if (! $runRecord->exists) {
            return;
        }

        $runRecord->delete();

        // invalidate caches
        $this->runs = null;
    }

    /**
     * Returns an import config model for a given UID
     */
    private function _getImportRunModel(string $uid, bool $withTrashed = false): ImportRunModel
    {
        return ImportRunModel::withTrashed($withTrashed)
            ->where('uid', $uid)
            ->first() ?? new ImportRunModel;
    }

    /**
     * Builds the base query for selecting non-deleted rows from the import_runs table.
     */
    private function _importRunQuery(): Builder
    {
        return DB::table(Table::IMPORT_RUNS)
            ->select([
                'import_runs.name',
                'import_runs.handle',
                'import_runs.description',
                'import_runs.steps',
                'import_runs.uid',
            ])
            ->orderBy('import_runs.name')
            ->orderBy('import_runs.handle')
            ->whereNull('import_runs.dateDeleted');
    }
}
