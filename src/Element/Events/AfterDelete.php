<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;

/**
 * @event AfterDelete The event that is triggered after the element is deleted.
 *
 * {@see \CraftCms\Cms\Element\Element::afterDelete()}
 */
final class AfterDelete
{
    public function __construct(
        public ElementInterface $element,
    ) {}
}
