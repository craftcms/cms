<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Events;

use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Element\Queries\Exceptions\QueryAbortedException;

/**
 * Dispatched each time an element query is prepared to run, after its params are applied, so listeners can adjust
 * the query with the joins, selects, and orders its params added in place.
 *
 * Listeners can add conditions and joins to the query. Their conditions are grouped apart from the query’s own, so an
 * `orWhere()` on the query can’t bypass them. Params set here no longer apply; set them from
 * {@see ElementQueryPreparing} instead. Throwing a {@see QueryAbortedException} makes the query return no results.
 *
 * Nested conditions, such as the closures passed to `where()`, don’t dispatch it; the query they belong to does.
 *
 * @since 6.0.0
 */
class ElementQueryPrepared
{
    public function __construct(
        /** @var ElementQuery<*> */
        public ElementQuery $query,
    ) {}
}
