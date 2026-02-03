<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;

/**
 * @event BeforeMoveInStructure The event that is triggered before the element is moved in a structure.
 *
 * Set `$isValid` to `false` to prevent the element from getting moved.
 *
 * {@see \CraftCms\Cms\Element\Concerns\Structurable::beforeMoveInStructure()}
 *
 * @since 6.0.0
 */
final class BeforeMoveInStructure
{
    /**
     * @param  ElementInterface  $element  The element
     * @param  int  $structureId  The structure ID
     * @param  bool  $isValid  Whether the move is valid
     */
    public function __construct(
        public ElementInterface $element,
        public int $structureId,
        public bool $isValid = true,
    ) {}
}
