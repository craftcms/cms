<?php

declare(strict_types=1);

namespace CraftCms\Cms\Site\Events;

use CraftCms\Cms\Site\Data\SiteGroup;

/**
 * @event SiteGroupDeleting The event that is triggered before a site group is deleted.
 *
 * @since 6.0.0
 */
class SiteGroupDeleting
{
    public function __construct(
        public SiteGroup $siteGroup,
    ) {}
}
