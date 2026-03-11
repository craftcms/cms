<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event BeforeRestore The event that is triggered before the element is restored.
 *
 * Set `$isValid` to `false` to prevent the element from getting restored.
 *
 * {@see Element::beforeRestore()}
 */
final class BeforeRestore
{
    use ValidatableEvent;

    public function __construct(
        public ElementInterface $element,
    ) {}
}
