<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Jobs;

use CraftCms\Cms\Import\Events\ImportChunkFinished;
use CraftCms\Cms\Import\Events\ImportChunkStarted;
use CraftCms\Cms\Import\Events\ImportStepStarted;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Queue\Job;
use CraftCms\Cms\Support\Facades\Import as ImportFacade;
use CraftCms\Cms\Support\Facades\ImportLog;
use CraftCms\Cms\Support\Facades\ImportPlan;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\ImportHelper;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Override;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class Import extends Job
{
    use Batchable;

    private int $defaultBatchSize = 5;

    /**
     * Promotes the owning import plan's UID/handle, the step's UID, the file path, the import run's ID
     * and the starting offset, then calls the parent constructor.
     *
     * @param  string  $importPlanId  The UID (or, for file-based import plans, the handle) of the import plan.
     * @param  string  $stepUid  The UID of the step being run.
     * @param  string  $filePath  The path to the file being imported.
     * @param  string  $runId  The unique ID of the import run this job belongs to.
     * @param  int  $start  The offset to start processing from.
     */
    public function __construct(
        private readonly string $importPlanId,
        private readonly string $stepUid,
        private readonly string $filePath,
        private readonly string $runId,
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

        $importPlan = ImportPlan::getImportPlanByUid($this->importPlanId) ?? ImportPlan::getImportPlanByHandle($this->importPlanId);

        if ($importPlan === null) {
            ImportLog::warning("Skipping import job for missing import plan \"{$this->importPlanId}\".");

            return;
        }

        /** @var BaseImporter|null $step */
        $step = collect($importPlan->steps ?? [])->firstWhere('uid', $this->stepUid);

        if ($step === null) {
            ImportLog::warning("Skipping import job for missing step \"{$this->stepUid}\" of import plan \"{$importPlan->name}\".");

            return;
        }

        $stepLabel = ImportFacade::stepLabel($importPlan, $step);

        try {
            // the file is only checked by the first chunk; later chunks read the local copy it downloaded, if it was a URL
            if ($this->start === 0) {
                $step->validate();
            } else {
                $step->validateSettings();
            }
        } catch (ValidationException $e) {
            ImportLog::warning("Skipping import job for invalid step \"$stepLabel\": ".implode(' ', $e->validator->errors()->all()));

            return;
        }

        $filePath = $this->filePath;

        // a remote file is downloaded once, by the step's first chunk, into Craft's storage, and the later chunks reuse it;
        // this assumes all chunk jobs run on the same server - if they don't, it might need to change to storing it on a (shared) disk
        if ($this->start === 0 && $step::isRemoteSource($step->source)) {
            $filePath = $step->downloadFile(Path::runtime(FinishImport::downloadsPath($this->runId)));
        }

        // get all the data
        $allData = ImportFacade::getFormattedData($filePath);
        // discard the part at the start that was already processed
        $data = array_slice($allData, $this->start);
        // count how many items we have to process
        $dataCount = count($data);
        // figure out our chunk limit
        $chunkLimit = $this->getChunkSize($step);

        // normalizing the UI/config-based matchCriteria only depends on the importer, so it
        // could be done once per step's chunk rather than for each root item that is being imported
        $matchCriteria = ImportHelper::normalizeMatchCriteriaFromImporterConfig($step);

        // if chunk limit is 0, it means this step's chunk size was set to zero to disable chunking of this step
        // so we want to go through all the data in one go
        if ($chunkLimit === 0) {
            $chunkLimit = $dataCount;
        }

        if ($this->start === 0) {
            event(new ImportStepStarted($importPlan, $step, $this->runId));
        }

        event(new ImportChunkStarted($importPlan, $step, $this->runId, $this->start, $chunkLimit));

        $processedCount = 0;
        $chunkHasFailures = false;

        for ($i = 0; $i < $chunkLimit; $i++) {
            // if we have less data than the limit, break
            if (! isset($data[$i])) {
                break;
            }

            $processedCount++;

            // import data
            try {
                ImportFacade::importItem($step, $data[$i], $matchCriteria, $this->runId);
            } catch (\Exception $e) {
                // log, let FinishImport know that this run had failures and proceed further
                ImportLog::warning('Couldn’t import a data item because of the following error: '.$e->getMessage(), ['step' => $stepLabel, 'data' => $data[$i]]);
                Cache::put(FinishImport::hasFailuresCacheKey($this->runId), true, now()->addDay());
                Cache::put(FinishImport::stepHasFailuresCacheKey($this->runId, $this->stepUid), true, now()->addDay());
                $chunkHasFailures = true;
            }
        }

        event(new ImportChunkFinished($importPlan, $step, $this->runId, $this->start, $processedCount, $chunkHasFailures));

        // if there's any data items left - add another job to the batch
        if ($dataCount - $chunkLimit > 0) {
            $this->batch()->add(new self($this->importPlanId, $this->stepUid, $filePath, $this->runId, ($this->start + $chunkLimit)));
        }
    }

    /**
     * Returns the step's configured chunk size, or the default chunk size if null.
     */
    private function getChunkSize(BaseImporter $step): int
    {
        // if chunk size was left empty, it was cast to a null, and we should use the default chunk size
        if (($step->batchSize ?? null) === null) {
            return $this->defaultBatchSize;
        }

        // otherwise, return the number specified in the step
        return $step->batchSize;
    }
}
