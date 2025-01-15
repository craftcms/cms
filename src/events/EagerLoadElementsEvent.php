<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;
use craft\base\Event;
use craft\elements\db\EagerLoadPlan;

/**
 * Eager-load event class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class EagerLoadElementsEvent extends Event
{
    /**
     * @var class-string<ElementInterface> The source element type
     */
    public string $elementType;

    /**
     * @var ElementInterface[] The source elements
     */
    public array $elements;

    /**
     * @var EagerLoadPlan[] The eager-loading plans
     */
    public array $with;
}
