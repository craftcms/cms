<?php

declare(strict_types=1);

namespace CraftCms\Cms\Site\Events;

use CraftCms\Cms\Site\Data\SiteGroup;

/**
 * @event DeletedSiteGroup The event that is triggered after a site group is deleted.
 */
final class DeletedSiteGroup
{
    public function __construct(
        public SiteGroup $siteGroup,
    ) {}
}
