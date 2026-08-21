<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Jobs;

use CraftCms\Cms\Queue\Job;
use CraftCms\Cms\Support\Facades\Import as ImportFacade;
use CraftCms\Cms\Support\Facades\ImportConfig;
use CraftCms\Cms\Support\Facades\ImportLog;
use Illuminate\Bus\Batchable;
use Override;

use function CraftCms\Cms\t;

class Import extends Job
{
    use Batchable;

    private int $defaultBatchSize = 5;

    /**
     * Promotes step config, file path, and starting offset, then calls the parent constructor.
     *
     * @param  array  $step  The step configuration.
     * @param  string  $filePath  The path to the file being imported.
     * @param  int  $start  The offset to start processing from.
     */
    public function __construct(
        private readonly array $step,
        private readonly string $filePath,
        private readonly int $start = 0,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function defaultDescription(): string
    {
        return t('Importing data (import job)');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch()->cancelled()) {
            return;
        }

        $config = ImportConfig::getConfigByUid($this->step['config']) ?? ImportConfig::getConfigByHandle($this->step['config']);

        // get all the data
        $allData = ImportFacade::getFormattedData($this->filePath);
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
            try {
                ImportFacade::importItem($config, $data[$i]);
            } catch (\Exception $e) {
                // log and proceed further
                ImportLog::warning('Couldn’t import a data item because of the following error: '.$e->getMessage(), ['config' => $config->name, 'data' => $data[$i]]);
            }
        }

        // if there's any data items left - add another job to the batch
        if ($dataCount - $batchLimit > 0) {
            $this->batch()->add(new Import($this->step, $this->filePath, ($this->start + $batchLimit)));
        }
    }

    /**
     * Returns the step's configured batch size, or the default batch size if null.
     */
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
