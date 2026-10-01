<?php

declare(strict_types=1);

namespace CraftCms\Cms\Site\Events;

use CraftCms\Cms\Site\Data\Site;

/**
 * @event SiteSaved The event that is triggered after a site is saved.
 *
 * @since 6.0.0
 */
class SiteSaved
{
    public function __construct(
        public Site $site,
        public bool $isNew = false,
        public ?int $oldPrimarySiteId = null,
    ) {}
}
