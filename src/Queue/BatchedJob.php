<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue;

use craft\base\Batchable;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\PHP;
use Override;

use function CraftCms\Cms\t;

/**
 * Base class for large jobs that may need to spawn additional jobs to complete the workload.
 *
 * Batched jobs process items in chunks and spawn follow-up jobs for remaining items.
 * This prevents memory issues and timeout errors for large data sets.
 *
 * Spawned jobs are cloned from the current job, so any public properties that are set
 * to objects which aren't `serialize()`-friendly should be excluded via `__sleep()`,
 * and any private/protected properties will need to be reset to their default values
 * via `__wakeup()` to avoid uninitialized property errors.
 */
abstract class BatchedJob extends Job
{
    /**
     * The number of items that should be processed in a single batch.
     */
    public int $batchSize = 100;

    /**
     * The index of the current batch (starting with 0).
     */
    public int $batchIndex = 0;

    /**
     * The offset to start fetching items by.
     */
    public int $itemOffset = 0;

    private ?Batchable $data = null;

    private ?int $totalItems = null;

    /**
     * Loads the batchable data.
     */
    abstract protected function loadData(): Batchable;

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
        $items = $this->data()->getSlice($this->itemOffset, $this->batchSize);

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
     * Returns the batchable data.
     */
    final protected function data(): Batchable
    {
        if (! isset($this->data)) {
            $this->data = $this->loadData();
        }

        return $this->data;
    }

    /**
     * Returns the total number of items across all batches.
     */
    final protected function totalItems(): int
    {
        if (! isset($this->totalItems)) {
            $this->totalItems = $this->data()->count();
        }

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
