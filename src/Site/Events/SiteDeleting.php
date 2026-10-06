<?php

declare(strict_types=1);

namespace CraftCms\Cms\Site\Events;

use CraftCms\Cms\Shared\Concerns\ValidatableEvent;
use CraftCms\Cms\Site\Data\Site;

/**
 * @event SiteDeleting The event that is triggered before a site is deleted.
 *
 * @since 6.0.0
 */
class SiteDeleting
{
    use ValidatableEvent;

    public function __construct(
        public Site $site,
        public ?int $transferContentTo,
    ) {}
}
