<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Jobs;

use CraftCms\Cms\Import\Data\ImportPlan as ImportPlanData;
use CraftCms\Cms\Import\Events\ImportFinished;
use CraftCms\Cms\Queue\Job;
use CraftCms\Cms\Support\Facades\Path;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Override;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class FinishImport extends Job
{
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
        File::deleteDirectory(Path::runtime(self::downloadsPath($this->runId), create: false));

        $hasFailures = (bool) Cache::pull(self::hasFailuresCacheKey($this->runId));

        event(new ImportFinished($this->importPlan, $this->importPlan->steps ?? [], $this->runId, $hasFailures));
    }

    /**
     * Returns the runtime-relative path of the directory that the given import run's remote files are downloaded to.
     *
     * @param  string  $runId  The unique ID of the import run.
     */
    public static function downloadsPath(string $runId): string
    {
        return "imports/{$runId}";
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
        return t('Finishing “{name}” import', ['name' => $this->importPlan->name]);
    }
}
