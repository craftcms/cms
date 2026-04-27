<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;

/**
 * @event AfterSave The event that is triggered after the element is saved.
 *
 * {@see Element::afterSave()}
 */
class AfterSave
{
    public function __construct(
        public ElementInterface $element,
        /** @var bool Whether the element is brand new */
        public bool $isNew,
    ) {}
}
