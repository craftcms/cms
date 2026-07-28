<?php

declare(strict_types=1);

namespace CraftCms\Cms\Blade;

use CraftCms\Cms\View\Contracts\TemplateRendererInterface;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\Blade;

class BladeRenderer implements TemplateRendererInterface
{
    public function supports(string $file): bool
    {
        return str_ends_with($file, '.blade.php');
    }

    public function renderTemplate(
        string $template,
        array $variables = [],
        ?TemplateMode $templateMode = null,
        ?string $resolvedTemplate = null,
    ): string {
        return $resolvedTemplate
            ? view()->file($resolvedTemplate, $variables)->render()
            : view($template, $variables)->render();
    }

    public function renderString(
        string $template,
        array $variables = [],
        TemplateMode $templateMode = TemplateMode::Site,
    ): string {
        return Blade::render($template, $variables);
    }
}
