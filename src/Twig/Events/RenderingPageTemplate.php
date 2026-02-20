<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Events;

use CraftCms\Cms\Shared\Concerns\ValidatableEvent;
use CraftCms\Cms\View\TemplateMode;

/**
 * @event RenderingPageTemplate The event that is triggered before a page template gets rendered
 */
final class RenderingPageTemplate
{
    use ValidatableEvent;

    public function __construct(
        public string $template,
        public array $variables,
        public TemplateMode $templateMode,
    ) {}
}
