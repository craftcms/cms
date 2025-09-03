<?php

namespace CraftCms\Cms\Updates\Events;

use CraftCms\Cms\Shared\Concerns\ValidatableEvent;
use CraftCms\Cms\Updates\Data\Update;

final class CriticalUpdateReleased
{
    use ValidatableEvent;

    public function __construct(
        public Update $update,
    ) {}
}
