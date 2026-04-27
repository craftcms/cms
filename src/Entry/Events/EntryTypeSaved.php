<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Events;

use CraftCms\Cms\Entry\Data\EntryType;

class EntryTypeSaved
{
    public function __construct(
        public EntryType $entryType,
        public bool $isNew,
    ) {}
}
