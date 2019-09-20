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
 * DefineEagerLoadingMapEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1
 */
class DefineEagerLoadingMapEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var ElementInterface[] An array of the source elements
     */
    public $sourceElements;

    /**
     * @var string The property handle used to identify which target elements should be included in the map
     */
    public $handle;

    /**
     * @var string|null The element type class to eager-load.
     */
    public $elementType;

    /**
     * @var array|null An array of element ID mappings, where each element is a sub-array with `source` and `target` keys.
     */
    public $map;

    /**
     * @var array|null Any criteria parameters that should be applied to the element query when fetching the eager-loaded elements.
     */
    public $criteria;
}
