<?php

declare(strict_types=1);

namespace CraftCms\Cms\Site\Events;

/**
 * @event SitesReordered The event that is triggered after the sites are reordered.
 *
 * @since 6.0.0
 */
class SitesReordered
{
    public function __construct(
        /** @var int[] */
        public array $siteIds,
    ) {}
}
