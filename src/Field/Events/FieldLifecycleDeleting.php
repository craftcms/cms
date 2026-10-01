<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @since 6.0.0
 */
class FieldLifecycleDeleting extends FieldEvent
{
    use ValidatableEvent;
}
