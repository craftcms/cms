<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use CraftCms\Cms\Twig\PageLifecycle;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\Facades\Blade;

#[Scoped]
class BladeRenderer
{
    private ?string $renderingTemplate = null;

    public bool $isRenderingTemplate {
        get => $this->isRenderingTemplate();
    }

    public private(set) bool $isRenderingPageTemplate = false;

    public function __construct(
        private readonly PageLifecycle $pageLifecycle,
    ) {}

    public function isRenderingTemplate(): bool
    {
        return isset($this->renderingTemplate);
    }

    public function renderFile(
        string $path,
        array $variables = [],
        ?TemplateMode $templateMode = null,
        ?string $template = null,
    ): string {
        $templateMode ??= TemplateMode::get();
        $template ??= $path;

        return $this->render(
            $template,
            $variables,
            $templateMode,
            fn (array $variables) => view()->file($path, $variables)->render(),
        );
    }

    public function renderPageFile(
        string $path,
        array $variables = [],
        ?TemplateMode $templateMode = null,
        ?string $template = null,
    ): string {
        $templateMode ??= TemplateMode::get();
        $template ??= $path;

        return $this->renderPage(
            $template,
            $variables,
            $templateMode,
            fn (array $variables, TemplateMode $templateMode, string $template) => $this->renderFile($path, $variables, $templateMode, $template),
        );
    }

    public function renderView(string $view, array $variables = [], ?TemplateMode $templateMode = null): string
    {
        $templateMode ??= TemplateMode::get();

        return $this->render(
            $view,
            $variables,
            $templateMode,
            fn (array $variables) => view($view, $variables)->render(),
        );
    }

    public function renderPageView(string $view, array $variables = [], ?TemplateMode $templateMode = null): string
    {
        $templateMode ??= TemplateMode::get();

        return $this->renderPage(
            $view,
            $variables,
            $templateMode,
            fn (array $variables, TemplateMode $templateMode, string $template) => $this->renderView($template, $variables, $templateMode),
        );
    }

    public function renderString(
        string $template,
        array $variables = [],
        TemplateMode $templateMode = TemplateMode::Site,
        bool $deleteCachedView = true,
    ): string {
        return $this->render(
            'string:'.$template,
            $variables,
            $templateMode,
            fn (array $variables) => Blade::render($template, $variables, deleteCachedView: $deleteCachedView),
        );
    }

    private function render(string $template, array $variables, TemplateMode $templateMode, callable $render): string
    {
        event($event = new Events\TemplateRendering(TemplateEngine::Blade, $template, $variables, $templateMode));

        if (! $event->isValid) {
            return '';
        }

        $template = $event->template;
        $variables = $event->variables;
        $templateMode = $event->templateMode;

        $oldTemplateMode = TemplateMode::get();
        TemplateMode::set($templateMode);

        $renderingTemplate = $this->renderingTemplate;
        $this->renderingTemplate = $template;

        try {
            $output = $render($variables, $templateMode, $template);
        } finally {
            $this->renderingTemplate = $renderingTemplate;
            TemplateMode::set($oldTemplateMode);
        }

        event($event = new Events\TemplateRendered(TemplateEngine::Blade, $template, $variables, $templateMode, $output));

        return $event->output;
    }

    private function renderPage(string $template, array $variables, TemplateMode $templateMode, callable $render): string
    {
        event($event = new Events\PageTemplateRendering(TemplateEngine::Blade, $template, $variables, $templateMode));

        if (! $event->isValid) {
            return '';
        }

        $template = $event->template;
        $variables = $event->variables;
        $templateMode = $event->templateMode;

        $isRenderingPageTemplate = $this->isRenderingPageTemplate;
        $this->isRenderingPageTemplate = true;

        try {
            $output = $this->pageLifecycle->wrap(fn () => $render($variables, $templateMode, $template));
        } finally {
            $this->isRenderingPageTemplate = $isRenderingPageTemplate;
        }

        event($event = new Events\PageTemplateRendered(TemplateEngine::Blade, $template, $variables, $templateMode, $output));

        return $event->output;
    }
}
