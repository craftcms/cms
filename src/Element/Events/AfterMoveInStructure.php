<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Concerns\Structurable;
use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * @event AfterMoveInStructure The event that is triggered after the element is moved in a structure.
 *
 * {@see Structurable::afterMoveInStructure()}
 */
class AfterMoveInStructure
{
    public function __construct(
        public ElementInterface $element,
        public int $structureId,
    ) {}
}
