<?php

declare(strict_types=1);

namespace CraftCms\Cms\Blade;

use CraftCms\Cms\View\BaseTemplateRenderer;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\Facades\Blade;

#[Scoped]
class BladeRenderer extends BaseTemplateRenderer
{
    public function supports(string $file): bool
    {
        return str_ends_with($file, '.blade.php');
    }

    public function renderTemplate(
        string $template,
        array $variables,
        ?TemplateMode $templateMode = null,
        ?string $resolvedTemplate = null,
    ): string {
        return $this->renderInternal(
            template: $template,
            variables: $variables,
            templateMode: $templateMode,
            render: fn (string $template, array $variables) => $resolvedTemplate
                ? view()->file($resolvedTemplate, $variables)->render()
                : view($template, $variables)->render()
        );
    }

    public function renderString(
        string $template,
        array $variables = [],
        TemplateMode $templateMode = TemplateMode::Site,
    ): string {
        return $this->renderInternal(
            'string:'.$template,
            $variables,
            $templateMode,
            fn () => Blade::render($template, $variables),
        );
    }
}
