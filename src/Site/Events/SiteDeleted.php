<?php

declare(strict_types=1);

namespace CraftCms\Cms\Site\Events;

use CraftCms\Cms\Site\Data\Site;

/**
 * @event SiteDeleted The event that is triggered after a site is deleted.
 */
class SiteDeleted
{
    public function __construct(
        public Site $site,
    ) {}
}
