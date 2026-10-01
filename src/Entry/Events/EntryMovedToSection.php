<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Events;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Section\Data\Section;

/**
 * @since 6.0.0
 */
class EntryMovedToSection
{
    public function __construct(
        public Entry $entry,
        public Section $section,
    ) {}
}
