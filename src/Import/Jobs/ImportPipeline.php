<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Jobs;

use CraftCms\Cms\Import\Data\ImportRun;
use CraftCms\Cms\Queue\Job;
use Illuminate\Support\Facades\Bus;
use Override;

use function CraftCms\Cms\t;

class ImportPipeline extends Job
{
    public function __construct(
        public array $steps,
        public ImportRun $run,
    ) {
        parent::__construct();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // each batch should `allowFailures()`
        // so that we don't cancel the batch when one job failed
        // maybe this should be customisable?
        // https://laravel.com/docs/12.x/queues#allowing-failures
        $steps = [];
        foreach ($this->steps as $step) {
            $steps[] = Bus::batch([$step['job']])->name($step['name'] ?? 'Importing step data')->allowFailures();
        }

        Bus::chain($steps)->dispatch();
    }

    #[Override]
    protected function defaultDescription(): string
    {
        return t("Importing “{$this->run->name}” data");
    }
}
