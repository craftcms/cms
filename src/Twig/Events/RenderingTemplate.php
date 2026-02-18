<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Events;

use CraftCms\Cms\Shared\Concerns\ValidatableEvent;
use CraftCms\Cms\View\TemplateMode;

/**
 * @event RenderingTemplate The event that is triggered before a template gets rendered
 */
final class RenderingTemplate
{
    use ValidatableEvent;

    public function __construct(
        public string $template,
        public array $variables,
        public TemplateMode $templateMode,
    ) {}
}
