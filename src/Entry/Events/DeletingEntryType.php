<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Events;

use CraftCms\Cms\Entry\Data\EntryType;

class DeletingEntryType
{
    public function __construct(
        public EntryType $entryType,
    ) {}
}
