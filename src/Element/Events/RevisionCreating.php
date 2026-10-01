<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Shared\Concerns\HandleableEvent;

/**
 * @since 6.0.0
 */
class RevisionCreating extends RevisionEvent
{
    use HandleableEvent;
}
