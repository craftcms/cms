<?php

declare(strict_types=1);

namespace CraftCms\Cms\Search\Events;

use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Search\SearchQuery;

/**
 * @event The event that is triggered after a search is performed.
 *
 * Any modifications to [[$scores]] will be respected.
 */
final class AfterSearch
{
    public function __construct(
        public ElementQueryInterface $elementQuery,
        public SearchQuery $query,
        public ?array $results = null,
        /** @var array<string,int>|null */
        public ?array $scores = null,
    ) {}
}
