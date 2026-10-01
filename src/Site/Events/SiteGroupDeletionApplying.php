<?php

declare(strict_types=1);

namespace CraftCms\Cms\Site\Events;

use CraftCms\Cms\Site\Data\SiteGroup;

/*
 * @event SiteGroupDeletionApplying The event that is triggered before a site group delete is applied to the database.
 */
/**
 * @since 6.0.0
 */
class SiteGroupDeletionApplying
{
    public function __construct(
        public SiteGroup $siteGroup,
    ) {}
}
