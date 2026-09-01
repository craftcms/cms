<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\DeletionBlockers\Contracts\DeletionBlockerInterface;
use CraftCms\Cms\Element\ElementCollection;

/**
 * @event DefineDeletionBlockers The event that is triggered when defining any blockers that should prevent a user from being deleted
 *
 * ---
 * ```php
 * use CraftCms\Cms\User\Elements\User;
 * use CraftCms\Cms\Element\Events\DefineDeletionBlockers;
 * use Illuminate\Facades\Event;
 *
 * Event::listen(function(DefineDeletionBlockers $event) {
 *     $event->blockers[] = // ...
 * });
 * ```
 *
 * @template TElement of ElementInterface
 */
class DefineDeletionBlockers
{
    public function __construct(
        /**
         * @var class-string<ElementInterface> The element type to define deletion blockers for.
         */
        public string $elementType,

        /**
         * @var ElementCollection<int, TElement> The elements to be deleted.
         */
        public ElementCollection $elements,

        /**
         * @var bool Whether the elements will be hard-deleted.
         */
        public bool $hardDelete,

        /**
         * @var DeletionBlockerInterface[] The defined blockers.
         */
        public array $blockers = [],
    ) {}
}
