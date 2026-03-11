<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\Element;

/**
 * @event AfterDelete The event that is triggered after the element is deleted.
 *
 * {@see Element::afterDelete()}
 */
final class AfterDelete
{
    public function __construct(
        public ElementInterface $element,
    ) {}
}
