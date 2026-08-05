<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\Events;

use CraftCms\Cms\View\TemplateMode;

/**
 * @event TemplateRendered The event that is triggered after a template is rendered
 */
class TemplateRendered
{
    /** @param array<string, mixed> $variables */
    public function __construct(
        public readonly string $rendererName,
        public readonly string $template,
        public readonly array $variables,
        public readonly TemplateMode $templateMode,
        public string $output,
    ) {}
}
