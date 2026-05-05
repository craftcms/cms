<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;

/**
 * @event ElementLifecyclePropagated The event that is triggered after the element is fully saved and propagated to other sites.
 *
 * {@see Element::afterPropagate()}
 */
class ElementLifecyclePropagated
{
    public function __construct(
        public ElementInterface $element,
        /** @var bool Whether the element is brand new */
        public bool $isNew,
    ) {}
}
