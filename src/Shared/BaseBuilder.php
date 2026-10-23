<?php

declare(strict_types=1);

namespace CraftCms\Cms\Shared;

use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Query;
use Illuminate\Contracts\Pagination\CursorPaginator as CursorPaginatorContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;
use Override;

/**
 * @template TModel of BaseModel
 *
 * @extends Builder<TModel>
 *
 * @since 6.0.0
 */
class BaseBuilder extends Builder
{
    /**
     * Compares a cursor’s dates in UTC, as they’re stored.
     *
     * A cursor takes each value from its model, which returns dates in the system
     * time zone (see [[BaseModel::asDateTime()]]), and binds them back as plain
     * strings, which aren’t converted like dates are.
     *
     * @param  list<string>|string  $columns
     * @return CursorPaginatorContract<int, TModel>
     */
    #[Override]
    protected function paginateUsingCursor($perPage, $columns = ['*'], $cursorName = 'cursor', $cursor = null)
    {
        if (! $cursor instanceof Cursor) {
            $cursor = is_string($cursor)
                ? Cursor::fromEncoded($cursor)
                : CursorPaginator::resolveCurrentCursor($cursorName, $cursor);
        }

        if ($cursor instanceof Cursor) {
            $parameters = $cursor->toArray();
            $pointsToNextItems = (bool) $parameters['_pointsToNextItems'];
            unset($parameters['_pointsToNextItems']);

            foreach ($parameters as $name => $value) {
                if (is_string($value) && $this->isDateAttribute($name)) {
                    $parameters[$name] = Query::prepareDateForDb(DateTimeHelper::toDateTime($value, true));
                }
            }

            $cursor = new Cursor($parameters, $pointsToNextItems);
        }

        return parent::paginateUsingCursor($perPage, $columns, $cursorName, $cursor);
    }

    private function isDateAttribute(string $name): bool
    {
        return in_array($name, $this->model->getDates(), true) || $this->model->hasCast($name, [
            'date',
            'datetime',
            'immutable_date',
            'immutable_datetime',
            'custom_datetime',
            'immutable_custom_datetime',
        ]);
    }
}
