<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Jobs;

use CraftCms\Cms\Import\Import as ImportService;
use CraftCms\Cms\Queue\Job;
use Illuminate\Bus\Batchable;

class Import extends Job
{
    use Batchable;

    private int $defaultBatchSize = 5;

    public function __construct(
        private readonly array $step,
        private readonly string $filePath,
        private readonly int $start = 0,
    ) {
        parent::__construct();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch()->cancelled()) {
            return;
        }

        $importService = app(ImportService::class);
        $config = $importService->getConfigByUid($this->step['config']) ?? $importService->getConfigByHandle($this->step['config']);

        // get all the data
        $allData = $importService->getData($this->filePath);
        // discard the part at the start that was already processed
        $data = array_slice($allData, $this->start);
        // count how many items we have to process
        $dataCount = count($data);
        // figure out our batch limit
        $batchLimit = $this->getBatchSize($this->step);

        // if batch limit is 0, it means this step's batch size was set to zero to disable batching of this step
        // so we want to go through all the data in one go
        if ($batchLimit === 0) {
            $batchLimit = $dataCount;
        }

        for ($i = 0; $i < $batchLimit; $i++) {
            // if we have less data than the limit, break
            if (! isset($data[$i])) {
                break;
            }

            // import data
            $importService->importItem($config, $data[$i]);
        }

        // if there's any data items left - add another job to the batch
        if ($dataCount - $batchLimit > 0) {
            $this->batch()->add(new Import($this->step, $this->filePath, ($this->start + $batchLimit)));
        }
    }

    private function getBatchSize(array $step): int
    {
        // if batch size was left empty, it was cast to a null, and we should use the default batch size
        if ($step['batchSize'] === null) {
            return $this->defaultBatchSize;
        }

        // otherwise, return the number specified in the step
        return (int) $step['batchSize'];
    }
}
