<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Data\EagerLoadPlan;

/**
 * Eager-load event class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Events\ElementsEagerLoading} instead.
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
