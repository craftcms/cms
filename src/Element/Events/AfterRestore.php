<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\Element;

/**
 * @event AfterRestore The event that is triggered after the element is restored.
 *
 * {@see Element::afterRestore()}
 */
class AfterRestore
{
    public function __construct(
        public ElementInterface $element,
    ) {}
}
