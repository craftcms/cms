<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Events;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Section\Data\Section;

class MovingEntryToSection
{
    public function __construct(
        public Entry $entry,
        public Section $section,
    ) {}
}
