<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Events;

use CraftCms\Cms\Entry\Data\EntryType;

/**
 * @since 6.0.0
 */
class EntryTypeSaving
{
    public function __construct(
        public EntryType $entryType,
        public bool $isNew,
    ) {}
}
