<?php

declare(strict_types=1);

namespace CraftCms\Cms\Section\Events;

use CraftCms\Cms\Section\Data\Section;

/**
 * @since 6.0.0
 */
class SectionDeleting
{
    public function __construct(
        public Section $section,
    ) {}
}
