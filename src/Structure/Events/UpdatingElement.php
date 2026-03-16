<?php

declare(strict_types=1);

namespace CraftCms\Cms\Structure\Events;

use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event MoveElementEvent The event that is triggered before an element is moved.
 *
 * You may set [[$isValid]] to `false` to prevent the
 * element from getting moved.
 */
class UpdatingElement extends UpdateElementEvent
{
    use ValidatableEvent;
}
