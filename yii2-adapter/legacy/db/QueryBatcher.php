<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use craft\base\Batchable;
use Illuminate\Contracts\Database\Query\Builder;
use yii\db\Connection as YiiConnection;
use yii\db\Query as YiiQuery;
use yii\db\QueryInterface;

/**
 * QueryBatcher provides a [[Batchable]] wrapper for a given [[QueryInterface]] object.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 4.4.0
 * @deprecated 6.0.0
 */
class QueryBatcher implements Batchable
{
    /**
     * Constructor
     *
     * :::warning
     * The query should have [[QueryInterface::orderBy()|`orderBy`]] set on it, ideally to the table’s primary key
     * column. That will ensure that the rows returned in result batches are consecutive.
     * :::
     */
    public function __construct(
        private QueryInterface|Builder $query,
        private ?YiiConnection $db = null,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        try {
            $query = clone $this->query;
            $query->offset(0)->limit(PHP_INT_MAX);
            $count = $query->count();
        } catch (QueryAbortedException) {
            return 0;
        }

        // Query::count() doesn't take the offset and limit into account
        if (isset($this->query->offset)) {
            $count = max($count - (int) $this->query->offset, 0);
        }
        if (isset($this->query->limit)) {
            $count = min((int) $this->query->limit, $count);
        }

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function getSlice(int $offset, int $limit): iterable
    {
        /** @var YiiQuery|Builder $query */
        $query = $this->query;

        if (is_int($query->limit)) {
            // Don't go passed the query's limit
            if ($offset >= $query->limit) {
                return [];
            }
            $limit = min($limit, $query->limit - $offset);
        }

        $queryOffset = $query->offset;
        $queryLimit = $query->limit;

        /**
         * Cannot use offset without limit in MySQL
         */
        if (is_null($queryLimit) && !is_null($queryOffset)) {
            $queryLimit = PHP_INT_MAX;
        }

        try {
            // For Laravel Builder, call all() without arguments
            // For Yii2 Query, pass the database connection
            if ($this->query instanceof Builder) {
                $slice = $query
                    ->offset((is_int($queryOffset) ? $queryOffset : 0) + $offset)
                    ->limit($limit)
                    ->get()
                    ->all();
            } else {
                $slice = $query
                    ->offset((is_int($queryOffset) ? $queryOffset : 0) + $offset)
                    ->limit($limit)
                    ->all($this->db);
            }
        } catch (QueryAbortedException) {
            $slice = [];
        }

        $query->offset($queryOffset);
        $query->limit($queryLimit);

        return $slice;
    }
}
