<?php

namespace CraftCms\Cms\Updates\Events;

use CraftCms\Cms\Shared\Concerns\ValidatableEvent;
use CraftCms\Cms\Updates\Data\Update;

/**
 * @since 6.0.0
 */
final class CriticalUpdateReleased
{
    use ValidatableEvent;

    public function __construct(
        public Update $update,
    ) {}
}
