<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\Events;

use CraftCms\Cms\View\Contracts\TemplateRendererInterface;
use CraftCms\Cms\View\TemplateMode;

/**
 * @event TemplateRendered The event that is triggered after a template is rendered
 */
class TemplateRendered
{
    public function __construct(
        /** @var class-string<TemplateRendererInterface> */
        public string $templateRenderer,
        public readonly string $template,
        public readonly array $variables,
        public readonly TemplateMode $templateMode,
        public string $output,
    ) {}
}
