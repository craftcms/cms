<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;
use yii\base\Event;

/**
 * DefineSourceSortOptionEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.5
 */
class DefineSourceSortOptionsEvent extends Event
{
    /**
     * @var string The element type class
     * @phpstan-var class-string<ElementInterface>
     */
    public string $elementType;

    /**
     * @var string The element source key
     */
    public string $source;

    /**
     * @var array The sort option definitions.
     *
     * Each sort option should be defined by an array with the following keys:
     *
     * - `label` – The sort option label
     * - `orderBy` – An array or comma-delimited string of columns to order the query by
     * - `attribute` _(optional)_ – The table attribute name that this option is associated with
     *   (required if `orderBy` is an array or more than one column name)
     */
    public array $sortOptions = [];
}
