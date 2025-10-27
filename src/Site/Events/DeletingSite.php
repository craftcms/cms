<?php

declare(strict_types=1);

namespace CraftCms\Cms\Site\Events;

use CraftCms\Cms\Shared\Concerns\ValidatableEvent;
use CraftCms\Cms\Site\Data\Site;

/**
 * @event DeletingSite The event that is triggered before a site is deleted.
 */
final class DeletingSite
{
    use ValidatableEvent;

    public function __construct(
        public Site $site,
        public ?int $transferContentTo,
    ) {}
}
