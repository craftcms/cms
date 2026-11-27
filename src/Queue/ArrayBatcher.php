<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue;

use CraftCms\Cms\Queue\Contracts\Batchable;

/**
 * Reads the items of an array in slices.
 *
 * A {@see BatchedJob} that returns one from `loadData()` usually builds it
 * from one of its own properties, so the whole array is serialized with every
 * batch of the job. Use it for small values, such as element IDs, and use a
 * query or a file for anything larger.
 *
 * ```php
 * public array $ids = [];
 *
 * protected function loadData(): Batchable
 * {
 *     return new ArrayBatcher($this->ids);
 * }
 * ```
 *
 * @since 6.0.0
 */
readonly class ArrayBatcher implements Batchable
{
    /**
     * @param  array<mixed>  $items  The items. Offsets are counted by position, so keys are ignored.
     */
    public function __construct(
        private array $items,
    ) {}

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return list<mixed>
     */
    public function getSlice(int $offset, int $limit): array
    {
        return array_values(array_slice($this->items, $offset, max($limit, 0)));
    }
}
