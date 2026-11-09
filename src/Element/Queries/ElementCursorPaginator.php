<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries;

use CraftCms\Cms\Support\Query;
use DateTimeInterface;
use Illuminate\Pagination\CursorPaginator;
use Override;

/**
 * A cursor paginator for element queries.
 *
 * Elements hold their dates in the system time zone, but the database stores them in UTC,
 * so date cursor values are written in UTC to compare correctly on the next page.
 *
 * @since 6.0.0
 *
 * @template TKey of array-key
 * @template TValue
 *
 * @extends CursorPaginator<TKey, TValue>
 */
class ElementCursorPaginator extends CursorPaginator
{
    #[Override]
    protected function ensureParameterIsPrimitive($parameter)
    {
        if ($parameter instanceof DateTimeInterface) {
            return Query::prepareDateForDb($parameter);
        }

        return parent::ensureParameterIsPrimitive($parameter);
    }
}
