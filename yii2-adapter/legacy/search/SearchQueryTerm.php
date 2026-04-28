<?php

namespace craft\search;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Search Query Term class
     * Represents a term in the search query.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 3.0.0
     * @deprecated in 6.0.0. Use {@see \CraftCms\Cms\Search\SearchQueryTerm} instead.
     */
    class SearchQueryTerm
    {
    }
}

class_alias(\CraftCms\Cms\Search\SearchQueryTerm::class, SearchQueryTerm::class);
