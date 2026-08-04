<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Events;

use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;

/**
 * @event EntryTypesResolving The event that is triggered when defining the available entry types for the entry
 *
 * @see Entry::getAvailableEntryTypes()
 */
class EntryTypesResolving
{
    public function __construct(
        public Entry $entry,
        /** @var EntryType[] */
        public array $entryTypes,
    ) {}
}
