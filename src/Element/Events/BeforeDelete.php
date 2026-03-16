<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event BeforeDelete The event that is triggered before the element is deleted.
 *
 * Set `$isValid` to `false` to prevent the element from getting deleted.
 *
 * {@see Element::beforeDelete()}
 */
class BeforeDelete
{
    use ValidatableEvent;

    public function __construct(
        public ElementInterface $element,
    ) {}
}
