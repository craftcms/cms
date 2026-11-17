<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database;

use CraftCms\Cms\Support\Query;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

/**
 * A row that one worker claims at a time. A claim older than its TTL counts as abandoned.
 *
 * A worker that is killed or hits a fatal error never releases its claim, so the row stays
 * claimed. Once the claim is older than the TTL, another worker can claim the row.
 *
 * @since 6.0.0
 */
readonly class Claim
{
    /**
     * @param  string  $column  The column holding the claim state
     * @param  mixed  $claimed  The column value of a claimed row
     * @param  mixed  $unclaimed  The column value of a row that no worker has claimed
     * @param  int  $ttl  Seconds after which a claim counts as abandoned
     * @param  string  $timestampColumn  The column holding the time the row was claimed
     */
    public function __construct(
        public string $column,
        public mixed $claimed,
        public mixed $unclaimed,
        public int $ttl,
        public string $timestampColumn = 'dateUpdated',
    ) {}

    /**
     * Limits the query to unclaimed rows and rows with abandoned claims.
     *
     * @template TQuery of Builder|EloquentBuilder
     *
     * @param  TQuery  $query
     * @return TQuery
     */
    public function whereClaimable(Builder|EloquentBuilder $query): Builder|EloquentBuilder
    {
        return $query->where(fn (\Illuminate\Contracts\Database\Query\Builder $query) => $query
            ->where($this->column, $this->unclaimed)
            ->orWhere(fn (\Illuminate\Contracts\Database\Query\Builder $query) => $query
                ->where($this->column, $this->claimed)
                ->where(fn (\Illuminate\Contracts\Database\Query\Builder $query) => $query
                    ->whereNull($this->timestampColumn)
                    ->orWhere($this->timestampColumn, '<', $this->cutoff()))));
    }

    /**
     * Limits the query to rows with a live claim.
     *
     * @template TQuery of Builder|EloquentBuilder
     *
     * @param  TQuery  $query
     * @return TQuery
     */
    public function whereClaimed(Builder|EloquentBuilder $query): Builder|EloquentBuilder
    {
        return $query
            ->where($this->column, $this->claimed)
            ->where($this->timestampColumn, '>=', $this->cutoff());
    }

    /**
     * Claims the unclaimed and abandoned rows the query matches, with one conditional update.
     *
     * @param  Builder|EloquentBuilder<covariant Model>  $query
     * @return bool Whether this call claimed any rows. `false` means another worker holds a live claim, or the rows no longer exist.
     */
    public function claim(Builder|EloquentBuilder $query): bool
    {
        return $this->whereClaimable($query)->update([
            $this->column => $this->claimed,
            $this->timestampColumn => Query::prepareDateForDb(now()),
        ]) > 0;
    }

    /**
     * Returns whether a claim made at the given time has been abandoned.
     */
    public function isAbandoned(?DateTimeInterface $claimedAt): bool
    {
        return $claimedAt === null || now()->diffInSeconds($claimedAt, true) > $this->ttl;
    }

    private function cutoff(): string
    {
        return Query::prepareDateForDb(now()->subSeconds($this->ttl));
    }
}
