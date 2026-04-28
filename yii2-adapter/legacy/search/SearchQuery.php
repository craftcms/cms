<?php

namespace craft\search;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Search Query class.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 3.0.0
     * @deprecated in 6.0.0. Use {@see \CraftCms\Cms\Search\SearchQuery} instead.
     */
    class SearchQuery
    {
    }
}

class_alias(\CraftCms\Cms\Search\SearchQuery::class, SearchQuery::class);
