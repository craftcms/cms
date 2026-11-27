<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue;

use CraftCms\Cms\Queue\Contracts\Batchable;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\PHP;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Queue\Jobs\SyncJob;
use InvalidArgumentException;
use LogicException;
use Override;

use function CraftCms\Cms\t;

/**
 * Base class for large jobs that may need to spawn additional jobs to complete the workload.
 *
 * Batched jobs process items in chunks and spawn follow-up jobs for remaining items.
 * This prevents memory issues and timeout errors for large data sets.
 *
 * Items come from a database or element query returned by `getQuery()`, or from any
 * other {@see Batchable} data source returned by `loadData()`, such as a {@see CsvBatcher}.
 *
 * Spawned jobs are cloned from the current job, so any public properties that are set
 * to objects which aren't `serialize()`-friendly should be excluded via `__sleep()`,
 * and any private/protected properties will need to be reset to their default values
 * via `__wakeup()` to avoid uninitialized property errors.
 *
 * @since 6.0.0
 */
abstract class BatchedJob extends Job
{
    /**
     * The number of items that should be processed in a single batch.
     */
    public int $batchSize = 100 {
        set {
            if ($value < 1) {
                throw new InvalidArgumentException('Batch size must be at least 1.');
            }

            $this->batchSize = $value;
        }
    }

    /**
     * The index of the current batch (starting with 0).
     */
    public int $batchIndex = 0;

    /**
     * The offset to start fetching items by.
     */
    public int $itemOffset = 0;

    private ?int $totalItems = null;

    private ?Batchable $data = null;

    /**
     * Returns the query that items should be fetched from.
     *
     * Jobs that override `loadData()` don't need to implement this.
     */
    protected function getQuery(): Builder
    {
        throw new LogicException(sprintf('%s must override getQuery() or loadData().', static::class));
    }

    /**
     * Loads the data source that items should be fetched from.
     *
     * By default, items are fetched from `getQuery()`.
     */
    protected function loadData(): Batchable
    {
        return new QueryBatcher($this->getQuery(), $this->queryOffset(), $this->queryLimit());
    }

    /**
     * Returns the data source for the current batch.
     *
     * It's loaded once per batch, so each batch reads it with the job's current
     * state, such as a saved position.
     */
    final protected function data(): Batchable
    {
        return $this->data ??= $this->loadData();
    }

    /**
     * Processes a single item.
     */
    abstract protected function processItem(mixed $item): void;

    /**
     * @return array<string>
     */
    public function __sleep(): array
    {
        return array_keys(get_object_vars($this));
    }

    public function handle(): void
    {
        try {
            $this->handleBatch();
        } finally {
            $this->data = null;
        }
    }

    private function handleBatch(): void
    {
        $items = $this->data()->getSlice(
            $this->itemOffset,
            min($this->batchSize, $this->totalItems() - $this->itemOffset),
        );

        $memoryLimit = PHP::sizeToBytes(ini_get('memory_limit'));
        $startMemory = $memoryLimit !== -1 ? memory_get_usage() : null;
        $start = microtime(true);

        if ($this->itemOffset === 0) {
            $this->before();
        }

        $this->beforeBatch();

        $i = 0;

        foreach ($items as $item) {
            // Check if job was cancelled between items
            if (! $this->shouldStillRun()) {
                $this->job?->delete();

                return;
            }

            $step = $this->itemOffset + 1;
            $total = $this->totalItems();
            $this->setProgress(
                (int) (($step / $total) * 100),
                I18N::prep('{step, number} of {total, number}', compact('step', 'total')),
            );

            $this->processItem($item);
            $this->itemOffset++;
            $i++;

            // Make sure we're not getting uncomfortably close to the memory limit
            if ($startMemory !== null) {
                $memory = memory_get_usage();
                $avgMemory = ($memory - $startMemory) / $i;

                if ($memory + ($avgMemory * 15) > $memoryLimit) {
                    break;
                }
            }

            // Make sure we don't hit the TTR, even if the next item takes twice as long as the average
            $runningTime = microtime(true) - $start;
            $avgRunningTime = $runningTime / $i;

            if ($runningTime + ($avgRunningTime * 2) > $this->timeout) {
                break;
            }
        }

        $this->afterBatch();

        // Spawn another job if there are more items
        if ($this->itemOffset < $this->totalItems()) {
            $this->spawnNextBatch();
        } else {
            $this->after();
        }
    }

    /**
     * Spawns the next batch job.
     */
    protected function spawnNextBatch(): void
    {
        $nextJob = clone $this;
        $nextJob->batchIndex++;
        $nextJob->data = null;

        if ($nextJob->job instanceof SyncJob) {
            $nextJob->handle();

            return;
        }

        $nextJob->job = null;
        dispatch($nextJob);
    }

    #[Override]
    public function getDescription(): string
    {
        $description = $this->description ?? $this->defaultDescription();
        $totalBatches = $this->totalBatches();

        if ($totalBatches <= 1) {
            return $description;
        }

        return t('{description} (batch {index, number} of {total, number})', [
            'description' => t($description),
            'index' => $this->batchIndex + 1,
            'total' => $totalBatches,
        ]);
    }

    /**
     * Called before the first item of the first batch.
     */
    protected function before(): void {}

    /**
     * Called after the last item of the last batch.
     */
    protected function after(): void {}

    /**
     * Called before the first item of the current batch.
     */
    protected function beforeBatch(): void {}

    /**
     * Called after the last item of the current batch.
     */
    protected function afterBatch(): void {}

    /**
     * Returns the initial query offset before batch pagination is applied.
     *
     * This only applies to items fetched from `getQuery()`.
     */
    protected function queryOffset(): int
    {
        return 0;
    }

    /**
     * Returns the maximum number of items to process across all batches.
     *
     * This only applies to items fetched from `getQuery()`.
     */
    protected function queryLimit(): ?int
    {
        return null;
    }

    /**
     * Returns the total number of items across all batches.
     */
    final protected function totalItems(): int
    {
        $this->totalItems ??= ($this->data ?? $this->loadData())->count();

        return $this->totalItems;
    }

    /**
     * Returns the total number of batches.
     */
    final protected function totalBatches(): int
    {
        return (int) ceil($this->totalItems() / $this->batchSize);
    }
}
