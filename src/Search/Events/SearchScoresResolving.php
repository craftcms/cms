<?php

declare(strict_types=1);

namespace CraftCms\Cms\Search\Events;

use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Search\SearchQuery;

/**
 * @event The event that is triggered after search result scores are calculated.
 *
 * Any modifications to [[$scores]] will be respected.
 */
class SearchScoresResolving
{
    public function __construct(
        public readonly ElementQueryInterface $elementQuery,
        public readonly SearchQuery $query,
        public readonly array $results,
        /** @var array<string,int> */
        public array $scores,
    ) {}
}
