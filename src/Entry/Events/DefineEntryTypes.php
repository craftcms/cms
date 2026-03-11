<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Events;

use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;

/**
 * @event DefineEntryTypes The event that is triggered when defining the available entry types for the entry
 *
 * @see Entry::getAvailableEntryTypes()
 */
final class DefineEntryTypes
{
    public function __construct(
        public Entry $entry,
        /** @var EntryType[] */
        public array $entryTypes,
    ) {}
}
