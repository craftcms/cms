<?php

declare(strict_types=1);

namespace CraftCms\Cms\Search\Events;

use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Search\SearchQuery;

/** @event The event that is triggered after a search is performed. */
readonly class SearchPerformed
{
    public function __construct(
        public ElementQueryInterface $elementQuery,
        public SearchQuery $query,
        /** @var list<array{elementId: int|string, siteId: int|string, keywords: string, attribute: string}> */
        public array $results,
        /** @var array<string,int> */
        public array $scores,
    ) {}
}
