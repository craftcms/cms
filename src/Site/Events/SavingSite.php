<?php

declare(strict_types=1);

namespace CraftCms\Cms\Site\Events;

use CraftCms\Cms\Site\Data\Site;

/**
 * @event SavingSite The event that is triggered before a site is saved.
 */
final class SavingSite
{
    public function __construct(
        public Site $site,
        public bool $isNew = false,
        public ?int $oldPrimarySiteId = null,
    ) {}
}
