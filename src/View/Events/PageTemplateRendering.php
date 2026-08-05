<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\Events;

use CraftCms\Cms\Shared\Concerns\ValidatableEvent;
use CraftCms\Cms\View\TemplateMode;

/**
 * @event PageTemplateRendering The event that is triggered before a page template gets rendered
 */
class PageTemplateRendering
{
    use ValidatableEvent;

    /** @param array<string, mixed> $variables */
    public function __construct(
        public string $template,
        public array $variables,
        public TemplateMode $templateMode,
    ) {}
}
