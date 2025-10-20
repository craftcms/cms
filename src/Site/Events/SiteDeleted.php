<?php

namespace CraftCms\Cms\Site\Events;

use CraftCms\Cms\Site\Data\Site;

/**
 * @event SiteDeleted The event that is triggered after a site is deleted.
 */
final class SiteDeleted
{
    public function __construct(
        public Site $site,
    ) {}
}
