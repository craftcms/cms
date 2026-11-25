<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Events;

use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Element\Queries\Exceptions\QueryAbortedException;

/**
 * Dispatched each time an element query is prepared to run, before its params are applied, so listeners can scope
 * every query of an element type, including its counts and pagination.
 *
 * Listeners can set params and add conditions to the query. Their conditions are grouped apart from the query’s own,
 * so an `orWhere()` on the query can’t bypass them. Throwing a {@see QueryAbortedException} makes the query return
 * no results.
 *
 * Nested conditions, such as the closures passed to `where()`, don’t dispatch it; the query they belong to does.
 * {@see ElementQueryPrepared} is dispatched once the params are applied.
 *
 * ```php
 * Event::listen(function (ElementQueryPreparing $event) {
 *     if ($event->query instanceof AssetQuery) {
 *         $event->query->whereIn('assets.folderId', $allowedFolderIds);
 *     }
 * });
 * ```
 *
 * @since 6.0.0
 */
class ElementQueryPreparing
{
    public function __construct(
        /** @var ElementQuery<*> */
        public ElementQuery $query,
    ) {}
}
