<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;
use craft\elements\db\EagerLoadPlan;
use CraftCms\Cms\Shared\Concerns\HandleableEvent;

/**
 * @event SetEagerLoadedElements event is triggered when setting eager-loaded elements.
 *
 * Set `handled` to `true` to prevent the elements from getting stored to the
 * private `$_eagerLoadedElements` array.
 */
final class SetEagerLoadedElements
{
    use HandleableEvent;

    /**
     * @param  ElementInterface  $element  The element the eager-loaded elements are being set on
     * @param  string  $handle  The handle that was used to eager-load the elements
     * @param  ElementInterface[]  $elements  The eager-loaded elements
     * @param  EagerLoadPlan  $plan  The eager-loading plan
     */
    public function __construct(
        public ElementInterface $element,
        public string $handle,
        public array $elements,
        public EagerLoadPlan $plan,
    ) {}
}
