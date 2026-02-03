<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;

/**
 * @event AfterRestore The event that is triggered after the element is restored.
 *
 * {@see \CraftCms\Cms\Element\Element::afterRestore()}
 */
final class AfterRestore
{
    public function __construct(
        public ElementInterface $element,
    ) {}
}
