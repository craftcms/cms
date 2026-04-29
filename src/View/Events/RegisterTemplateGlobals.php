<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\Events;

class RegisterTemplateGlobals
{
    public function __construct(
        public array $globals,
    ) {}
}
