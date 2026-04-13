<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Concerns\Structurable;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event BeforeMoveInStructure The event that is triggered before the element is moved in a structure.
 *
 * Set `$isValid` to `false` to prevent the element from getting moved.
 *
 * {@see Structurable::beforeMoveInStructure()}
 */
class BeforeMoveInStructure
{
    use ValidatableEvent;

    public function __construct(
        public ElementInterface $element,
        public int $structureId,
    ) {}
}
