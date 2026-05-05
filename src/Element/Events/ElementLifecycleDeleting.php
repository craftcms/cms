<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event ElementLifecycleDeleting The event that is triggered before the element is deleted.
 *
 * Set `$isValid` to `false` to prevent the element from getting deleted.
 *
 * {@see Element::beforeDelete()}
 */
class ElementLifecycleDeleting
{
    use ValidatableEvent;

    public function __construct(
        public ElementInterface $element,
    ) {}
}
