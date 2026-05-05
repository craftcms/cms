<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Concerns\Structurable;
use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * @event ElementMovedInStructure The event that is triggered after the element is moved in a structure.
 *
 * {@see Structurable::afterMoveInStructure()}
 */
class ElementMovedInStructure
{
    public function __construct(
        public ElementInterface $element,
        public int $structureId,
    ) {}
}
