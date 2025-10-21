<?php

namespace CraftCms\Cms\Section\Events;

use craft\models\Section;

final class SectionDeleted
{
    public function __construct(
        public Section $section,
    ) {}
}
