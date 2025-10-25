<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Events;

use craft\elements\Entry;
use CraftCms\Cms\Section\Data\Section;

final class MovingEntryToSection
{
    public function __construct(
        public Entry $entry,
        public Section $section,
    ) {
    }
}
