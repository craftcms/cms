<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\Events;

use CraftCms\Cms\Shared\Concerns\ValidatableEvent;
use CraftCms\Cms\View\TemplateMode;

/**
 * @event TemplateRendering The event that is triggered before a template gets rendered
 */
class TemplateRendering
{
    use ValidatableEvent;

    /** @param array<string, mixed> $variables */
    public function __construct(
        public string $template,
        public array $variables,
        public TemplateMode $templateMode,
    ) {}
}
