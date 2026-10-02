<?php

declare(strict_types=1);

namespace CraftCms\Cms\Update\Events;

use CraftCms\Cms\Shared\Concerns\ValidatableEvent;
use CraftCms\Cms\Update\Data\Update;

/**
 * @since 6.0.0
 */
class CriticalUpdateReleased
{
    use ValidatableEvent;

    public function __construct(
        public Update $update,
    ) {}
}
