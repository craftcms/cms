<?php

namespace craft\search;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Search Query Term Group class
     * Contains multiple SearchQueryTerm instances, each representing a term in the search query that was combined by "OR".
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 3.0.0
     * @deprecated in 6.0.0. Use {@see \CraftCms\Cms\Search\SearchQueryTermGroup} instead.
     */
    class SearchQueryTermGroup
    {
    }
}

class_alias(\CraftCms\Cms\Search\SearchQueryTermGroup::class, SearchQueryTermGroup::class);
