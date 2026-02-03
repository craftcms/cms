<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;
use craft\elements\db\EagerLoadPlan;

/**
 * SetEagerLoadedElements event is triggered when setting eager-loaded elements.
 *
 * Set `handled` to `true` to prevent the elements from getting stored to the
 * private `$_eagerLoadedElements` array.
 *
 * @since 6.0.0
 */
final class SetEagerLoadedElements
{
    /**
     * @param  ElementInterface  $element  The element the eager-loaded elements are being set on
     * @param  string  $handle  The handle that was used to eager-load the elements
     * @param  ElementInterface[]  $elements  The eager-loaded elements
     * @param  EagerLoadPlan  $plan  The eager-loading plan
     * @param  bool  $handled  Whether the event has been handled
     */
    public function __construct(
        public ElementInterface $element,
        public string $handle,
        public array $elements,
        public EagerLoadPlan $plan,
        public bool $handled = false,
    ) {}
}
