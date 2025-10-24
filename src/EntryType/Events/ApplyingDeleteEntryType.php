<?php

namespace CraftCms\Cms\EntryType\Events;

use CraftCms\Cms\EntryType\Data\EntryType;

final class ApplyingDeleteEntryType
{
    public function __construct(
        public EntryType $entryType,
    ) {}
}
