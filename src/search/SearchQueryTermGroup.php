<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\search;

/**
 * Search Query Term Group class
 * Contains multiple SearchQueryTerm instances, each representing a term in the search query that was combined by "OR".
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class SearchQueryTermGroup
{
    /**
     * @var SearchQueryTerm[]
     */
    public array $terms;

    /**
     * Constructor
     *
     * @param SearchQueryTerm[] $terms
     */
    public function __construct(array $terms = [])
    {
        $this->terms = $terms;
    }
}
