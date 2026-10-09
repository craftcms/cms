<?php

declare(strict_types=1);

namespace CraftCms\Cms\Site\Events;

use CraftCms\Cms\Site\Data\Site;

/**
 * @event PrimarySiteChanged The event that is triggered after the primary site has changed
 *
 * @since 6.0.0
 */
class PrimarySiteChanged
{
    public function __construct(
        public Site $site,
    ) {}
}
