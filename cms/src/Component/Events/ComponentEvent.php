<?php

namespace CraftCms\Cms\Component\Events;

use CraftCms\Cms\Component\Contracts\SavableComponentInterface;
use CraftCms\Cms\Support\Events\Concerns\ValidatableEvent;

final class ComponentEvent
{
    use ValidatableEvent;

    public function __construct(
        public SavableComponentInterface $component,
        public readonly bool $isNew = false,
    ) {}
}
