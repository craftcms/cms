<?php

namespace CraftCms\Cms\Section\Events;

use craft\models\Section;

final class ApplyingSectionDelete
{
    public function __construct(
        public Section $section,
    ) {}
}
