<?php

declare(strict_types=1);

namespace CraftCms\Cms\Blade;

use CraftCms\Cms\View\Contracts\TemplateRendererInterface;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Factory;
use Illuminate\View\View;
use Illuminate\View\ViewName;

class BladeRenderer implements TemplateRendererInterface
{
    public function __construct(private readonly Factory $factory) {}

    public function supports(string $file): bool
    {
        return str_ends_with($file, '.blade.php');
    }

    /** @param array<string, mixed> $variables */
    public function renderTemplate(
        string $template,
        array $variables = [],
        ?TemplateMode $templateMode = null,
        ?string $resolvedTemplate = null,
    ): string {
        if ($resolvedTemplate === null) {
            return view($template, $variables)->render();
        }

        $view = new View(
            $this->factory,
            $this->factory->getEngineFromPath($resolvedTemplate),
            ViewName::normalize($template),
            $resolvedTemplate,
            $variables,
        );
        $this->factory->callCreator($view);

        return $view->render();
    }

    /** @param array<string, mixed> $variables */
    public function renderString(
        string $template,
        array $variables = [],
        TemplateMode $templateMode = TemplateMode::Site,
    ): string {
        return Blade::render($template, $variables);
    }
}
