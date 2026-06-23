<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\Events;

use CraftCms\Cms\Shared\Concerns\ValidatableEvent;
use CraftCms\Cms\View\Contracts\TemplateRendererInterface;
use CraftCms\Cms\View\TemplateMode;

/**
 * @event PageTemplateRendering The event that is triggered before a page template gets rendered
 */
class PageTemplateRendering
{
    use ValidatableEvent;

    public function __construct(
        /** @var class-string<TemplateRendererInterface> */
        public string $templateRenderer,
        public string $template,
        public array $variables,
        public TemplateMode $templateMode,
    ) {}
}
