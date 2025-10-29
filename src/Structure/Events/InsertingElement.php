<?php

declare(strict_types=1);

namespace CraftCms\Cms\Structure\Events;

use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event MoveElementEvent The event that is triggered before an element is inserted into a structure.
 *
 * You may set [[$isValid]] to `false` to prevent the
 * element from getting inserted.
 */
final class InsertingElement extends UpdateElementEvent
{
    use ValidatableEvent;
}
