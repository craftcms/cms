<?php

declare(strict_types=1);

namespace CraftCms\Cms\Site\Events;

use CraftCms\Cms\Site\Data\SiteGroup;

/**
 * @event SiteGroupSaving The event that is triggered before a site group is saved.
 *
 * @since 6.0.0
 */
class SiteGroupSaving
{
    public function __construct(
        public SiteGroup $siteGroup,
        public bool $isNew = false,
    ) {}
}
