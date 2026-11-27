<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue;

use CraftCms\Cms\Queue\Contracts\Batchable;
use Illuminate\Contracts\Database\Query\Builder;

/**
 * Reads the results of a database or element query in slices.
 *
 * The query should be ordered, ideally by its primary key, so each slice
 * continues where the last one ended.
 *
 * @since 6.0.0
 */
readonly class QueryBatcher implements Batchable
{
    /**
     * @param  int  $offset  The number of results to skip before the first slice.
     * @param  int|null  $limit  The maximum number of results across all slices.
     */
    public function __construct(
        private Builder $query,
        private int $offset = 0,
        private ?int $limit = null,
    ) {}

    public function count(): int
    {
        $count = max((clone $this->query)->count() - $this->offset, 0);

        return $this->limit !== null ? min($count, $this->limit) : $count;
    }

    public function getSlice(int $offset, int $limit): iterable
    {
        if ($this->limit !== null) {
            $limit = min($limit, $this->limit - $offset);
        }

        return (clone $this->query)
            ->offset($this->offset + $offset)
            ->limit(max($limit, 0))
            ->get();
    }
}
