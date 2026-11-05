<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Jobs;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Import\Data\ImportPlan as ImportPlanData;
use CraftCms\Cms\Import\Events\ImportFinished;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Queue\Job;
use CraftCms\Cms\Support\Facades\I18N;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Override;

/**
 * @since 6.0.0
 */
class FinishImport extends Job
{
    /**
     * The directory on the import disk that import runs store their items in.
     */
    public const string SPOOL_ROOT = 'imports';

    /**
     * Promotes the import plan and run ID, then calls the parent constructor.
     *
     * @param  ImportPlanData  $importPlan  The import plan that was imported.
     * @param  string  $runId  The unique ID of this import run.
     */
    public function __construct(
        public ImportPlanData $importPlan,
        public string $runId,
    ) {
        parent::__construct();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Storage::disk(Cms::config()->getImportDisk())->deleteDirectory(self::spoolPath($this->runId));

        $hasFailures = (bool) Cache::pull(self::hasFailuresCacheKey($this->runId));

        Import::releaseRunLock($this->importPlan, $this->runId);

        event(new ImportFinished($this->importPlan, $this->importPlan->steps ?? [], $this->runId, $hasFailures));
    }

    /**
     * Returns the path of the directory on the import disk that the given import run's steps store their items in.
     *
     * @param  string  $runId  The unique ID of the import run.
     */
    public static function spoolPath(string $runId): string
    {
        return self::SPOOL_ROOT."/{$runId}";
    }

    /**
     * Returns the cache key used to flag that an item or job of the given import run failed.
     *
     * @param  string  $runId  The unique ID of the import run.
     */
    public static function hasFailuresCacheKey(string $runId): string
    {
        return "import-run:{$runId}:hasFailures";
    }

    /**
     * Returns the cache key used to flag that an item of the given step of an import run failed.
     *
     * @param  string  $runId  The unique ID of the import run.
     * @param  string  $stepUid  The UID of the step.
     */
    public static function stepHasFailuresCacheKey(string $runId, string $stepUid): string
    {
        return "import-run:{$runId}:step:{$stepUid}:hasFailures";
    }

    #[Override]
    protected function defaultDescription(): string
    {
        return I18N::prep('Finishing “{name}” import', ['name' => $this->importPlan->name]);
    }
}
