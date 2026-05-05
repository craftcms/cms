<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;

/**
 * @event ElementLifecycleDeleted The event that is triggered after the element is deleted.
 *
 * {@see Element::afterDelete()}
 */
class ElementLifecycleDeleted
{
    public function __construct(
        public ElementInterface $element,
    ) {}
}
