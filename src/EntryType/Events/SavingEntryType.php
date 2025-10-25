<?php

declare(strict_types=1);

namespace CraftCms\Cms\EntryType\Events;

use CraftCms\Cms\EntryType\Data\EntryType;

final class SavingEntryType
{
    public function __construct(
        public EntryType $entryType,
        public bool $isNew,
    ) {}
}
