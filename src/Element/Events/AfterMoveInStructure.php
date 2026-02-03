<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;

/**
 * @event AfterMoveInStructure The event that is triggered after the element is moved in a structure.
 *
 * {@see \CraftCms\Cms\Element\Concerns\Structurable::afterMoveInStructure()}
 *
 * @since 6.0.0
 */
final class AfterMoveInStructure
{
    /**
     * @param  ElementInterface  $element  The element
     * @param  int  $structureId  The structure ID
     */
    public function __construct(
        public ElementInterface $element,
        public int $structureId,
    ) {}
}
