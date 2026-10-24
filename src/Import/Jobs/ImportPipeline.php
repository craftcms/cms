<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Jobs;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Import\Data\ImportPlan as ImportPlanData;
use CraftCms\Cms\Import\Events\ImportStarted;
use CraftCms\Cms\Import\Events\ImportStepFinished;
use CraftCms\Cms\Import\Import as ImportService;
use CraftCms\Cms\Queue\Job;
use CraftCms\Cms\Support\Facades\I18N;
use Illuminate\Bus\Batch;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Bus\PendingChain;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Override;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class ImportPipeline extends Job
{
    /**
     * Promotes steps, import plan and run ID, then calls the parent constructor.
     *
     * @param  array<int, array{name: string, uid: string|null, job: Import}>  $steps  The steps to run in this pipeline.
     * @param  ImportPlanData  $importPlan  The import plan this pipeline belongs to.
     * @param  string  $runId  The unique ID of this import run.
     */
    public function __construct(
        public array $steps,
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
        if (empty($this->steps)) {
            ImportService::releaseRunLock($this->importPlan, $this->runId);

            return;
        }

        $importPlan = $this->importPlan;
        $runId = $this->runId;

        event(new ImportStarted($importPlan, $importPlan->steps ?? [], $runId));
        $steps = [];

        foreach ($this->steps as $step) {
            $stepUid = $step['uid'];

            // each batch should `allowFailures()`
            // so that we don't cancel the batch when one job failed
            // https://laravel.com/docs/13.x/queues#allowing-failures
            $steps[] = Bus::batch([$step['job']])
                ->name($step['name'] ?? 'Importing step data')
                ->allowFailures()
                // batched jobs are pushed onto the batch's queue, rather than the one each job was given
                ->onQueue(Cms::config()->queueName)
                // runs once, after every job in this step has run (failed ones included)
                ->finally(function (Batch $batch) use ($importPlan, $stepUid, $runId) {
                    // the step's stored items aren't needed anymore, even if the batch was canceled and FinishImport won't run
                    Storage::disk(Cms::config()->getImportDisk())->deleteDirectory(Import::spoolPath($runId, $stepUid));

                    // let FinishImport know that at least one step had failures
                    if ($batch->hasFailures()) {
                        Cache::put(FinishImport::hasFailuresCacheKey($runId), true, now()->addDay());
                    }

                    $itemsFailed = (bool) Cache::pull(FinishImport::stepHasFailuresCacheKey($runId, $stepUid));
                    $step = collect($importPlan->steps ?? [])->firstWhere('uid', $stepUid);

                    if ($step !== null) {
                        event(new ImportStepFinished($importPlan, $step, $runId, $batch->hasFailures() || $itemsFailed));
                    }

                    // FinishImport won't run, so it can't mark the import plan as no longer running
                    if ($batch->cancelled()) {
                        ImportService::releaseRunLock($importPlan, $runId);
                    }
                })
                ->when($this->connection !== null, fn (PendingBatch $batch) => $batch->onConnection($this->connection));
        }

        // runs only after the last step's batch has finished, and never if a batch was canceled
        Bus::chain([...$steps, new FinishImport($importPlan, $runId)])
            ->onQueue(Cms::config()->queueName)
            ->when($this->connection !== null, fn (PendingChain $chain) => $chain->onConnection($this->connection))
            ->dispatch();
    }

    /**
     * Marks the import plan as no longer running if the pipeline couldn't queue its steps.
     */
    public function failed(): void
    {
        ImportService::releaseRunLock($this->importPlan, $this->runId);
    }

    #[Override]
    protected function defaultDescription(): string
    {
        return I18N::prep('Importing “{name}” data', [
            'name' => $this->importPlan->name,
        ]);
    }
}
