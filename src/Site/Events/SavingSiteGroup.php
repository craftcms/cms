<?php

declare(strict_types=1);

namespace CraftCms\Cms\Site\Events;

use CraftCms\Cms\Site\Data\SiteGroup;

/**
 * @event SavingSiteGroup The event that is triggered before a site group is saved.
 */
final class SavingSiteGroup
{
    public function __construct(
        public SiteGroup $siteGroup,
        public bool $isNew = false,
    ) {}
}
