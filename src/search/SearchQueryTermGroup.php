<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\search;

/**
 * Search Query Term Group class
 *
 * Contains multiple SearchQueryTerm instances, each representing a term in the search query that was combined by "OR".
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class SearchQueryTermGroup
{
    // Properties
    // =========================================================================

    /**
     * @var array
     */
    public $terms;

    // Public Methods
    // =========================================================================

    /**
     * Constructor
     *
     * @param array $terms
     *
     * @return SearchQueryTermGroup
     */
    public function __construct($terms = [])
    {
        $this->terms = $terms;
    }
}
