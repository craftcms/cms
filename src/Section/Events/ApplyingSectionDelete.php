<?php

declare(strict_types=1);

namespace CraftCms\Cms\Section\Events;

use CraftCms\Cms\Section\Data\Section;

final class ApplyingSectionDelete
{
    public function __construct(
        public Section $section,
    ) {}
}
