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
    private string $file;

    public function supports(string $file): bool
    {
        if (str_ends_with($file, '.blade.php')) {
            $this->file = $file;

            return true;
        }

        return false;
    }

    public function renderTemplate(string $template, array $variables, ?TemplateMode $templateMode = null): string
    {
        return $this->renderInternal(
            template: $template,
            variables: $variables,
            templateMode: $templateMode,
            render: fn (string $template, array $variables) => $template
                ? view($template, $variables)->render()
                : view()->file($this->file, $variables)->render()
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
