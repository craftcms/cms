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
 * @since 3.1.0
 */
class DefineEagerLoadingMapEvent extends Event
{
    /**
     * @var ElementInterface[] An array of the source elements
     */
    public array $sourceElements;

    /**
     * @var string The property handle used to identify which target elements should be included in the map
     */
    public string $handle;

    /**
     * @var string|null The element type class to eager-load.
     * @phpstan-var class-string<ElementInterface>|null
     */
    public ?string $elementType = null;

    /**
     * @var array|null An array of element ID mappings, where each element is a sub-array with `source` and `target` keys.
     */
    public ?array $map = null;

    /**
     * @var array|null Any criteria parameters that should be applied to the element query when fetching the eager-loaded elements.
     */
    public ?array $criteria = null;
}
