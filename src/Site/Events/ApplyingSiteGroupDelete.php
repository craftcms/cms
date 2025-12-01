<?php

declare(strict_types=1);

namespace CraftCms\Cms\Site\Events;

use CraftCms\Cms\Site\Data\SiteGroup;

/*
 * @event ApplyingSiteGroupDelete The event that is triggered before a site group delete is applied to the database.
 */
final class ApplyingSiteGroupDelete
{
    public function __construct(
        public SiteGroup $siteGroup,
    ) {}
}
