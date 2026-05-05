<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

class FieldLifecycleDeleting extends FieldEvent
{
    use ValidatableEvent;
}
