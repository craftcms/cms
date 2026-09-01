<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\Events;

class TemplateGlobalsResolving
{
    /** @param array<string, mixed> $globals */
    public function __construct(
        public array $globals,
    ) {}
}
