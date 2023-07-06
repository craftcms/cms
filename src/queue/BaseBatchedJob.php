<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue;

use Craft;
use craft\base\Batchable;
use craft\helpers\ConfigHelper;
use craft\helpers\Queue as QueueHelper;
use craft\i18n\Translation;

/**
 * BaseBatchedJob is the base class for large jobs that may need to spawn
 * additional jobs to complete the workload.
 *
 * ::: warning
 * Batched jobs should *always* be pushed to the queue using [[QueueHelper::push()]],
 * so the `priority` and `ttr` settings can be maintained for additional spawned jobs.
 * :::
 *
 * :::warning
 * Spawned jobs are cloned from the current job, so any public properties that are set to objects which aren’t
 * `serialize()`-friendly should be excluded via `__sleep()`, and any private/protected properties will need
 * to be reset to their default values via `__wakeup()` to avoid uninitialized property errors.
 * :::
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
abstract class BaseBatchedJob extends BaseJob
{
    /**
     * @var int The number of items that should be processed in a single batch
     */
    public int $batchSize = 100;

    /**
     * @var int The index of the current batch (starting with `0`)
     */
    public int $batchIndex = 0;

    /**
     * @var int The offset to start fetching items by.
     */
    public int $itemOffset = 0;

    /**
     * @var int|null The job’s priority
     */
    public ?int $priority = null;

    /**
     * @var int|null The job’s TTR
     */
    public ?int $ttr = null;

    private ?Batchable $_data = null;
    private ?int $_totalItems = null;

    public function __sleep(): array
    {
        return array_keys(Craft::getObjectVars($this));
    }

    /**
     * Loads the batchable data.
     *
     * @return Batchable
     */
    abstract protected function loadData(): Batchable;

    /**
     * Returns the batchable data.
     *
     * @return Batchable
     */
    final protected function data(): Batchable
    {
        if (!isset($this->_data)) {
            $this->_data = $this->loadData();
        }
        return $this->_data;
    }

    /**
     * Returns the total number of items across all the batches.
     *
     * @return int
     */
    final protected function totalItems(): int
    {
        if (!isset($this->_totalItems)) {
            $this->_totalItems = $this->data()->count();
        }
        return $this->_totalItems;
    }

    /**
     * Returns the total number of batches.
     *
     * @return int
     */
    final protected function totalBatches(): int
    {
        return (int)ceil($this->totalItems() / $this->batchSize);
    }

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        $items = $this->data()->getSlice($this->itemOffset, $this->batchSize);
        $totalInBatch = is_array($items) ? count($items) : iterator_count($items);

        $memoryLimit = ConfigHelper::sizeInBytes(ini_get('memory_limit'));
        $startMemory = $memoryLimit != -1 ? memory_get_usage() : null;

        $i = 0;

        foreach ($items as $item) {
            $this->setProgress($queue, $i / $totalInBatch, Translation::prep('app', '{step, number} of {total, number}', [
                'step' => $this->itemOffset + 1,
                'total' => $this->totalItems(),
            ]));
            $this->processItem($item);
            $this->itemOffset++;
            $i++;

            // Make sure we're not getting uncomfortably close to the memory limit, every 10 items
            if ($startMemory !== null && $i % 10 === 0) {
                $memory = memory_get_usage();
                $avgMemory = ($memory - $startMemory) / $i;
                if ($memory + ($avgMemory * 15) > $memoryLimit) {
                    break;
                }
            }
        }

        // Spawn another job if there are more items
        if ($this->itemOffset < $this->totalItems()) {
            $nextJob = clone $this;
            $nextJob->batchIndex++;
            QueueHelper::push($nextJob, $this->priority, 0, $this->ttr, $queue);
        }
    }

    /**
     * Processes an item.
     *
     * @param mixed $item
     */
    abstract protected function processItem(mixed $item): void;

    /**
     * @inheritdoc
     */
    final public function getDescription(): ?string
    {
        $description = $this->description ?? $this->defaultDescription();
        $totalBatches = $this->totalBatches();
        if ($totalBatches <= 1) {
            return $description;
        }
        return Craft::t('app', '{description} (batch {index, number} of {total, number})', [
            'description' => Translation::translate($description),
            'index' => $this->batchIndex + 1,
            'total' => $totalBatches,
        ]);
    }
}
