<?php

declare(strict_types=1);

namespace CraftCms\Cms\Blade;

use CraftCms\Cms\Livewire\LivewireComponents;
use CraftCms\Cms\Support\Facades\Template;
use CraftCms\Cms\View\Contracts\TemplateRendererInterface;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Factory;
use Illuminate\View\View;
use Illuminate\View\ViewName;
use Livewire\Livewire;

/**
 * Renders Blade templates.
 *
 * When Livewire is installed, site templates written as Livewire single-file components are mounted
 * instead. A component that is the page template, such as a section’s entry template, is mounted as a
 * full-page component in its layout. One included from another template is mounted on its own.
 *
 * @since 6.0.0
 */
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

        if (($templateMode ?? TemplateMode::get()) === TemplateMode::Site && class_exists(Livewire::class) && app()->bound('livewire')) {
            $components = app(LivewireComponents::class);

            if ($name = $components->nameForTemplate($resolvedTemplate)) {
                return Template::isRenderingPageRootTemplate()
                    ? $components->renderPageTemplate($name, $variables)
                    : $components->mount($name, $variables);
            }
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
