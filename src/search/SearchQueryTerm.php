<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\search;

use yii\base\BaseObject;

/**
 * Search Query Term class
 * Represents a term in the search query.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SearchQueryTerm extends BaseObject
{
    // Properties
    // =========================================================================

    /**
     * @var bool
     */
    public $exclude = false;

    /**
     * @var bool
     */
    public $exact = false;

    /**
     * @var bool
     */
    public $subLeft = false;

    /**
     * @var bool
     */
    public $subRight = true;

    /**
     * @var null
     */
    public $attribute;

    /**
     * @var null
     */
    public $term;

    /**
     * @var bool|null
     */
    public $phrase;
}
