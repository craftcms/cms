<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Events;

use CraftCms\Cms\View\TemplateMode;

/**
 * @event PageTemplateRendered The event that is triggered after a page template is rendered
 */
final class PageTemplateRendered
{
    public function __construct(
        public readonly string $template,
        public readonly array $variables,
        public readonly TemplateMode $templateMode,
        public string $output,
    ) {}
}
