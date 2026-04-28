<?php

declare(strict_types=1);

namespace CraftCms\Cms\Search\Events;

use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Search\SearchQuery;

/**
 * @event The event that is triggered before the results are scored.
 *
 * Any modifications to [[$results]] will be respected when results are scored.
 * Set [[$scores]] to override the resulting element scores returned by [[Search::searchElements()]].
 */
class BeforeScoreResults
{
    public function __construct(
        public ElementQueryInterface $elementQuery,
        public SearchQuery $query,
        public ?array $results = null,
        /** @var array<string,int>|null */
        public ?array $scores = null,
    ) {}
}
