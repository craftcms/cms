<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Shared\Concerns\HandleableEvent;

class CreatingRevision extends RevisionEvent
{
    use HandleableEvent;
}
