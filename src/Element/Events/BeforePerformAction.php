<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementActionInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

class BeforePerformAction
{
    use ValidatableEvent;

    public function __construct(
        public ElementActionInterface $action,
        public ElementQueryInterface $query,
        public ?string $message = null,
    ) {}
}
