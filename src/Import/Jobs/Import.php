<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Jobs;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Import\Data\ImportPlan as ImportPlanData;
use CraftCms\Cms\Import\Events\ImportChunkFinished;
use CraftCms\Cms\Import\Events\ImportChunkStarted;
use CraftCms\Cms\Import\Events\ImportStepStarted;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Import\ImportHelper;
use CraftCms\Cms\Queue\Job;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Import as ImportFacade;
use CraftCms\Cms\Support\Facades\ImportLog;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\PHP;
use Illuminate\Bus\Batchable;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Override;
use RuntimeException;
use Throwable;

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

    private int $defaultBatchSize = 100;

    /**
     * Promotes the owning import plan, the step's UID, the file path, the import run's ID
     * and the starting offset, then calls the parent constructor.
     *
     * @param  ImportPlanData  $importPlan  The import plan, as it was when the import was dispatched.
     * @param  string  $stepUid  The UID of the step being run.
     * @param  string  $filePath  The path to the file being imported.
     * @param  string  $runId  The unique ID of the import run this job belongs to.
     * @param  int  $start  The offset to start processing from.
     * @param  int|null  $totalCount  The total number of the step's items, if an earlier chunk stored them on the import disk.
     */
    public function __construct(
        private readonly ImportPlanData $importPlan,
        private readonly string $stepUid,
        private readonly string $filePath,
        private readonly string $runId,
        private readonly int $start = 0,
        private readonly ?int $totalCount = null,
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
            ImportLog::warning("Skipping import job for missing step \"{$this->stepUid}\" of import plan \"{$this->importPlan->name}\".", [
                'importPlan' => $this->importPlan->handle ?? $this->importPlan->name,
                'stepUid' => $this->stepUid,
                'runId' => $this->runId,
                'start' => $this->start,
            ]);

            return;
        }

        $stepLabel = ImportFacade::stepLabel($this->importPlan, $step);

        try {
            // the file is only checked by the first chunk; later chunks read the items it stored on the import disk
            if ($this->start === 0) {
                $step->validate();
            } else {
                $step->validateSettings();
            }
        } catch (ValidationException $e) {
            ImportLog::warning("Skipping import job for invalid step \"$stepLabel\".", [
                'importPlan' => $this->importPlan->handle ?? $this->importPlan->name,
                'stepUid' => $this->stepUid,
                'runId' => $this->runId,
                'start' => $this->start,
                'validation' => $this->start === 0 ? 'full' : 'settings',
                'errors' => $e->errors(),
            ]);

            return;
        }

        // figure out our chunk limit
        $chunkLimit = $this->getChunkSize($step);
        // if chunk limit is 0, it means this step's chunk size was set to zero to disable chunking of this step
        $chunkingDisabled = $chunkLimit === 0;

        // the source is only parsed by the step's first chunk, which stores the items it leaves for the later chunks
        // on the import disk, so that each of them reads just its own items, even if it runs on a different server
        if ($this->totalCount === null || $chunkingDisabled) {
            $allData = $this->parseSource($step);
            $totalCount = count($allData);
            // discard the part at the start that was already processed
            $data = array_slice($allData, $this->start);
        } else {
            $allData = null;
            $totalCount = $this->totalCount;
            $data = $this->readSpooledItems($this->start, $chunkLimit);
        }

        // count how many items we have left to process
        $dataCount = max(0, $totalCount - $this->start);

        // normalizing the UI/config-based matchCriteria only depends on the importer, so it
        // could be done once per step's chunk rather than for each root item that is being imported
        $matchCriteria = ImportHelper::normalizeMatchCriteriaFromImporterConfig($step);

        // with chunking disabled, we want to go through all the data in one go
        if ($chunkingDisabled) {
            $chunkLimit = $dataCount;
        }

        // a step that needs more chunks stores its items before importing any, so the time that takes counts towards this job's limits;
        // with chunking disabled, the data isn't split into pieces, so a follow-up job parses the source again
        $spooled = $this->totalCount !== null && ! $chunkingDisabled;
        $startTime = microtime(true);

        if (! $spooled && ! $chunkingDisabled && $allData !== null && $dataCount > $chunkLimit) {
            $this->spoolItems($allData, $this->start, $chunkLimit);
            $spooled = true;
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
            $this->batch()->add(new self(
                $this->importPlan,
                $this->stepUid,
                $this->filePath,
                $this->runId,
                $this->start + $processedCount,
                $spooled ? $totalCount : null,
            ));
        }
    }

    /**
     * Returns the directory on the import disk that the given step of an import run stores its items in.
     *
     * @param  string  $runId  The unique ID of the import run.
     * @param  string  $stepUid  The UID of the step.
     */
    public static function spoolPath(string $runId, string $stepUid): string
    {
        return FinishImport::spoolPath($runId)."/{$stepUid}";
    }

    /**
     * Reads and formats the step's source, downloading it first if it's remote.
     *
     * @return array<mixed>
     */
    private function parseSource(BaseImporter $step): array
    {
        if (! $step::isRemoteSource($step->source)) {
            return ImportFacade::getFormattedData($this->filePath);
        }

        // this should stay local as it's downloaded, parsed and deleted in the first chunk
        // (the parsed items are shared with later chunks, so stored on the importDisk)
        $filePath = $step->downloadFile(Path::temp());

        try {
            return ImportFacade::getFormattedData($filePath);
        } finally {
            @unlink($filePath);
        }
    }

    /**
     * Stores the items from the given offset's piece onwards on the import disk, split into pieces of `$pieceSize` items,
     * where piece `n` holds the items from offset `n * $pieceSize`.
     *
     * @param  array<mixed>  $allData
     */
    private function spoolItems(array $allData, int $start, int $pieceSize): void
    {
        $disk = Storage::disk(Cms::config()->getImportDisk());
        $totalCount = count($allData);

        for ($piece = intdiv($start, $pieceSize); $piece * $pieceSize < $totalCount; $piece++) {
            $items = array_slice($allData, $piece * $pieceSize, $pieceSize);
            $disk->put($this->piecePath($piece), Json::encode($items, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        }
    }

    /**
     * Returns up to `$limit` of the stored items, starting at the given offset.
     *
     * @return list<mixed>
     */
    private function readSpooledItems(int $start, int $limit): array
    {
        $disk = Storage::disk(Cms::config()->getImportDisk());
        $items = [];
        $piece = intdiv($start, $limit);
        $offset = $start % $limit;

        // an earlier chunk that stopped short of its limit leaves this one starting partway through a piece
        while (count($items) < $limit && $this->totalCount !== null && $piece * $limit < $this->totalCount) {
            $contents = $disk->get($this->piecePath($piece));

            if ($contents === null) {
                throw new RuntimeException("Couldn’t find the stored import data for step \"{$this->stepUid}\" of run \"{$this->runId}\".");
            }

            array_push($items, ...array_slice(Json::decode($contents), $offset));
            $offset = 0;
            $piece++;
        }

        return array_slice($items, 0, $limit);
    }

    private function piecePath(int $piece): string
    {
        return self::spoolPath($this->runId, $this->stepUid)."/{$piece}.json";
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
