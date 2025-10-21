<?php

namespace CraftCms\Cms\Section\Events;

use craft\models\Section;

final class SectionSaved
{
    public function __construct(
        public Section $section,
        public bool $isNew = false,
    ) {}
}
