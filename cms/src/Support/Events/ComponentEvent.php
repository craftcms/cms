<?php

namespace CraftCms\Cms\Support\Events;

use CraftCms\Cms\Support\Contracts\SavableComponentInterface;
use CraftCms\Cms\Support\Events\Concerns\ValidatableEvent;

final class ComponentEvent
{
    use ValidatableEvent;

    public function __construct(
        public SavableComponentInterface $component,
        readonly bool $isNew = false,
    ) {}
}
