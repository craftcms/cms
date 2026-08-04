<?php

declare(strict_types=1);

namespace CraftCms\Cms\Shared\Concerns;

trait HandleableEvent
{
    public bool $handled = false;
}
