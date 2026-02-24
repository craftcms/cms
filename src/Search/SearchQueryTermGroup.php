<?php

declare(strict_types=1);

namespace CraftCms\Cms\Search;

/**
 * Contains multiple SearchQueryTerm instances, each representing
 * a term in the search query that was combined by "OR".
 */
final class SearchQueryTermGroup
{
    /** @param  SearchQueryTerm[] $terms */
    public function __construct(
        public array $terms = [],
    ) {}
}
