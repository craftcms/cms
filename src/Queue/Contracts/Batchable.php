<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue\Contracts;

use Countable;
use CraftCms\Cms\Queue\BatchedJob;

/**
 * A data source that can be counted and read in slices, such as by a
 * {@see BatchedJob}.
 *
 * @since 6.0.0
 */
interface Batchable extends Countable
{
    /**
     * Returns a slice of the items.
     *
     * @return iterable<mixed>
     */
    public function getSlice(int $offset, int $limit): iterable;
}
