<?php

namespace CraftCms\Cms\Section\Events;

use craft\models\Section;

final class DeletingSection
{
    public function __construct(
        public Section $section,
    ) {}
}
