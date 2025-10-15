<?php

namespace CraftCms\Cms\Site\Events;

use CraftCms\Cms\Site\Data\Site;

/*
 * @event ApplyingSiteDelete The event that is triggered before a site delete is applied to the database.
 */
final class ApplyingSiteDelete
{
    public function __construct(
        public Site $site,
    ) {}
}
