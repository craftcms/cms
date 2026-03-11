<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\Concerns\Structurable;

/**
 * @event AfterMoveInStructure The event that is triggered after the element is moved in a structure.
 *
 * {@see Structurable::afterMoveInStructure()}
 */
final class AfterMoveInStructure
{
    public function __construct(
        public ElementInterface $element,
        public int $structureId,
    ) {}
}
