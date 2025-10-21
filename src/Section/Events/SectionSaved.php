<?php

namespace CraftCms\Cms\Section\Events;

use CraftCms\Cms\Section\Data\Section;

final class SectionSaved
{
    public function __construct(
        public Section $section,
        public bool $isNew = false,
    ) {}
}
