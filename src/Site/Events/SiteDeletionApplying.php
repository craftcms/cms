<?php

declare(strict_types=1);

namespace CraftCms\Cms\Site\Events;

use CraftCms\Cms\Site\Data\Site;

/*
 * @event SiteDeletionApplying The event that is triggered before a site delete is applied to the database.
 */
class SiteDeletionApplying
{
    public function __construct(
        public Site $site,
    ) {}
}
