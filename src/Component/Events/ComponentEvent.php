<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Events;

use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

final class ComponentEvent
{
    use ValidatableEvent;

    public function __construct(
        public mixed $component,
        public readonly bool $isNew = false,
    ) {}
}
