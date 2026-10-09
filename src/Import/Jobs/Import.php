<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Jobs;

use CraftCms\Cms\Import\Data\ImportPlan as ImportPlanData;
use CraftCms\Cms\Import\Events\ImportChunkFinished;
use CraftCms\Cms\Import\Events\ImportChunkStarted;
use CraftCms\Cms\Import\Events\ImportStepStarted;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Queue\Job;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Import as ImportFacade;
use CraftCms\Cms\Support\Facades\ImportLog;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\ImportHelper;
use CraftCms\Cms\Support\PHP;
use Illuminate\Bus\Batchable;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Override;
use Throwable;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class Import extends Job
{
    use Batchable;

    /**
     * The number of times the job may be attempted; a retried chunk would import its items again.
     */
    public int $tries = 1;

    /**
     * Whether the job should be marked as failed on timeout, rather than retried.
     */
    public bool $failOnTimeout = true;

    private int $defaultBatchSize = 5;

    /**
     * Promotes the owning import plan, the step's UID, the file path, the import run's ID
     * and the starting offset, then calls the parent constructor.
     *
     * @param  ImportPlanData  $importPlan  The import plan, as it was when the import was dispatched.
     * @param  string  $stepUid  The UID of the step being run.
     * @param  string  $filePath  The path to the file being imported.
     * @param  string  $runId  The unique ID of the import run this job belongs to.
     * @param  int  $start  The offset to start processing from.
     */
    public function __construct(
        private readonly ImportPlanData $importPlan,
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
        return I18N::prep('Importing data (import job)');
    }

    #[Override]
    public function middleware(): array
    {
        return [...parent::middleware(), new SkipIfBatchCancelled];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /** @var BaseImporter|null $step */
        $step = collect($this->importPlan->steps ?? [])->firstWhere('uid', $this->stepUid);

        if ($step === null) {
            ImportLog::warning("Skipping import job for missing step \"{$this->stepUid}\" of import plan \"{$this->importPlan->name}\".");

            return;
        }

        $stepLabel = ImportFacade::stepLabel($this->importPlan, $step);

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
        $totalCount = count($allData);
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
            event(new ImportStepStarted($this->importPlan, $step, $this->runId));
        }

        event(new ImportChunkStarted($this->importPlan, $step, $this->runId, $this->start, $chunkLimit));

        $processedCount = 0;
        $chunkHasFailures = false;
        $canceled = false;
        $memoryLimit = PHP::sizeToBytes(ini_get('memory_limit'));
        $startMemory = $memoryLimit !== -1 ? memory_get_usage() : null;
        $startTime = microtime(true);

        for ($i = 0; $i < $chunkLimit; $i++) {
            // if we have less data than the limit, break
            if (! isset($data[$i])) {
                break;
            }

            // stop if the job was canceled between items, without queuing the next chunk
            if (! $this->shouldStillRun()) {
                $canceled = true;
                break;
            }

            $processedCount++;

            // import data
            try {
                ImportFacade::importItem($step, $data[$i], $matchCriteria, $this->runId);
            } catch (Throwable $e) {
                // log, let FinishImport know that this run had failures and proceed further
                ImportLog::warning('Couldn’t import a data item because of the following error: '.$e->getMessage(), [
                    'step' => $stepLabel,
                    'data' => $data[$i],
                    'exception' => $e,
                ]);
                Cache::put(FinishImport::hasFailuresCacheKey($this->runId), true, now()->addDay());
                Cache::put(FinishImport::stepHasFailuresCacheKey($this->runId, $this->stepUid), true, now()->addDay());
                $chunkHasFailures = true;
            }

            $itemNumber = $this->start + $processedCount;
            $this->setProgress(
                (int) (($itemNumber / $totalCount) * 100),
                I18N::prep('{step, number} of {total, number}', ['step' => $itemNumber, 'total' => $totalCount]),
            );

            // leave the rest of the data to the next chunk if this one is getting close to the memory limit or the timeout
            if ($this->isNearingLimits($processedCount, $startTime, $startMemory, $memoryLimit)) {
                break;
            }
        }

        event(new ImportChunkFinished($this->importPlan, $step, $this->runId, $this->start, $processedCount, $chunkHasFailures));

        // if there's any data items left - add another job to the batch
        if (! $canceled && $processedCount < $dataCount) {
            $this->batch()->add(new self($this->importPlan, $this->stepUid, $filePath, $this->runId, $this->start + $processedCount));
        }
    }

    /**
     * Returns whether processing another item could take the job past the memory limit or the timeout.
     */
    private function isNearingLimits(int $processedCount, float $startTime, ?int $startMemory, int $memoryLimit): bool
    {
        if ($startMemory !== null) {
            $memory = memory_get_usage();
            $avgMemory = ($memory - $startMemory) / $processedCount;

            if ($memory + ($avgMemory * 15) > $memoryLimit) {
                return true;
            }
        }

        // make sure we don't hit the timeout, even if the next item takes twice as long as the average
        $runningTime = microtime(true) - $startTime;

        return $runningTime + (($runningTime / $processedCount) * 2) > $this->timeout;
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
