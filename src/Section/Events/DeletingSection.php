<?php

namespace CraftCms\Cms\Section\Events;

use CraftCms\Cms\Section\Data\Section;

final class DeletingSection
{
    public function __construct(
        public Section $section,
    ) {}
}
