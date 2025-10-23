<?php

namespace CraftCms\Cms\EntryType\Events;

use CraftCms\Cms\EntryType\Data\EntryType;

final class EntryTypeSaved
{
    public function __construct(
        public EntryType $entryType,
        public bool $isNew,
    ) {}
}
