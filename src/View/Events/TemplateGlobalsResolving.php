<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\Events;

/**
 * @since 6.0.0
 */
class TemplateGlobalsResolving
{
    /** @param array<string, mixed> $globals */
    public function __construct(
        public array $globals,
    ) {}
}
