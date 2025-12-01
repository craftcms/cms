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
     * @var bool|null
     */
    public ?bool $subLeft = null;

    /**
     * @var bool|null
     */
    public ?bool $subRight = null;

    /**
     * @var bool|null
     */
    public ?bool $exclude = null;

    /**
     * @var bool|null
     */
    public ?bool $exact = null;

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
