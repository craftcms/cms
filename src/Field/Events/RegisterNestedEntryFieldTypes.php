<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use Illuminate\Support\Collection;

/**
 * @event RegisterNestedEntryFieldTypes The event that is triggered when registering field types which manage nested entries.
 *
 * These field types must implement [[ElementContainerFieldInterface]].
 */
class RegisterNestedEntryFieldTypes
{
    public function __construct(
        /** @var Collection<class-string<ElementContainerFieldInterface>> */
        public Collection $types,
    ) {}
}
