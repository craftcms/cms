<?php

declare(strict_types=1);

namespace CraftCms\Cms\Site\Events;

/**
 * @event SitesReordering The event that is triggered before the sites are reordered.
 */
class SitesReordering
{
    public function __construct(
        /** @var int[] */
        public array $siteIds,
    ) {}
}
