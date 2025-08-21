<?php

namespace CraftCms\Cms\Component\Events;

use CraftCms\Cms\Support\Events\Concerns\ValidatableEvent;

/**
 * @since 6.0.0
 */
final class ComponentEvent
{
    use ValidatableEvent;

    public function __construct(
        public mixed $component,
        public readonly bool $isNew = false,
    ) {}
}
