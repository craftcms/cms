<?php

declare(strict_types=1);

namespace CraftCms\Cms\Site\Events;

/**
 * @event ReorderingSites The event that is triggered before the sites are reordered.
 */
class ReorderingSites
{
    public function __construct(
        /** @var int[] */
        public array $siteIds,
    ) {}
}
