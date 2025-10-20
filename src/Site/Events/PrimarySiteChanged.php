<?php

namespace CraftCms\Cms\Site\Events;

use CraftCms\Cms\Site\Data\Site;

/**
 * @event PrimarySiteChanged The event that is triggered after the primary site has changed
 */
final class PrimarySiteChanged
{
    public function __construct(
        public Site $site,
    ) {}
}
