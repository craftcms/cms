<?php

namespace CraftCms\Cms\Site\Events;

use CraftCms\Cms\Site\Data\SiteGroup;

/**
 * @event SavedSiteGroup The event that is triggered after a site group is saved.
 */
final class SavedSiteGroup
{
    public function __construct(
        public SiteGroup $siteGroup,
        public bool $isNew = false,
    ) {}
}
