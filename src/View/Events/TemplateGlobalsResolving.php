<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\Events;

class TemplateGlobalsResolving
{
    public function __construct(
        public array $globals,
    ) {}
}
