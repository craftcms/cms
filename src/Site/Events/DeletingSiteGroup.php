<?php

declare(strict_types=1);

namespace CraftCms\Cms\Site\Events;

use CraftCms\Cms\Site\Data\SiteGroup;

/**
 * @event DeletingSiteGroup The event that is triggered before a site group is deleted.
 */
final class DeletingSiteGroup
{
    public function __construct(
        public SiteGroup $siteGroup,
    ) {}
}
