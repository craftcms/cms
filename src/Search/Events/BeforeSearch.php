<?php

declare(strict_types=1);

namespace CraftCms\Cms\Search\Events;

use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Search\SearchQuery;

/**
 * @event The event that is triggered before a search is performed.
 */
class BeforeSearch
{
    public function __construct(
        public ElementQueryInterface $elementQuery,
        public SearchQuery $query,
    ) {}
}
