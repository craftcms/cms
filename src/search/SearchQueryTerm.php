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
 * @since 3.0.0
 */
class SearchQueryTerm extends BaseObject
{
    /**
     * @var bool
     */
    public bool $exclude = false;

    /**
     * @var bool
     */
    public bool $exact = false;

    /**
     * @var bool
     */
    public bool $subLeft = false;

    /**
     * @var bool
     */
    public bool $subRight = true;

    /**
     * @var string|null
     */
    public ?string $attribute = null;

    /**
     * @var string|null
     */
    public ?string $term = null;

    /**
     * @var bool
     */
    public bool $phrase = false;
}
