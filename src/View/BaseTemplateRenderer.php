<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use CraftCms\Cms\View\Contracts\TemplateRendererInterface;
use CraftCms\Cms\View\Events\PageTemplateRendered;
use CraftCms\Cms\View\Events\PageTemplateRendering;
use CraftCms\Cms\View\Events\TemplateRendered;
use CraftCms\Cms\View\Events\TemplateRendering;
use Illuminate\Support\Facades\Log;

abstract class BaseTemplateRenderer implements TemplateRendererInterface
{
    public function __construct(
        protected PageLifecycle $pageLifecycle,
    ) {}

    protected ?string $renderingTemplate = null;

    public bool $isRenderingTemplate {
        get => $this->isRenderingTemplate();
    }

    public protected(set) bool $isRenderingPageTemplate = false;

    /**
     * Returns whether a template is currently being rendered.
     */
    public function isRenderingTemplate(): bool
    {
        return isset($this->renderingTemplate);
    }

    public function isRenderingPageTemplate(): bool
    {
        return $this->isRenderingPageTemplate;
    }

    /**
     * Renders a page template (with beginPage/endPage lifecycle).
     *
     * Delegates output buffering, BeginPage/EndPage events, and placeholder
     * replacement to {@see PageLifecycle}, keeping template rendering concerns
     * separate from page structure and asset injection.
     */
    public function renderPageTemplate(
        string $template,
        array $variables,
        ?TemplateMode $templateMode = null,
        ?string $resolvedTemplate = null,
    ): string {
        $templateMode ??= TemplateMode::get();

        event($event = new PageTemplateRendering(static::class, $template, $variables, $templateMode));

        if (! $event->isValid) {
            return '';
        }

        $template = $event->template;
        $variables = $event->variables;
        $templateMode = $event->templateMode;

        $isRenderingPageTemplate = $this->isRenderingPageTemplate;
        $this->isRenderingPageTemplate = true;

        try {
            $output = $this->pageLifecycle->wrap(
                fn () => $this->renderTemplate($template, $variables, $templateMode, $resolvedTemplate),
            );
        } finally {
            $this->isRenderingPageTemplate = $isRenderingPageTemplate;
        }

        event($event = new PageTemplateRendered(static::class, $template, $variables, $templateMode, $output));

        return $event->output;
    }

    protected function renderInternal(string $template, array $variables, ?TemplateMode $templateMode, callable $render): string
    {
        $templateMode ??= TemplateMode::get();

        event($event = new TemplateRendering(static::class, $template, $variables, $templateMode));

        if (! $event->isValid) {
            return '';
        }

        $template = $event->template;
        $variables = $event->variables;
        $templateMode = $event->templateMode;

        Log::debug("Rendering template: $template", [__METHOD__]);

        $oldTemplateMode = TemplateMode::get();
        TemplateMode::set($templateMode);

        // Render and return
        $renderingTemplate = $this->renderingTemplate;
        $this->renderingTemplate = $template;

        try {
            $output = $render($template, $variables);
        } finally {
            $this->renderingTemplate = $renderingTemplate;

            TemplateMode::set($oldTemplateMode);
        }

        event($event = new TemplateRendered(static::class, $template, $variables, $templateMode, $output));

        return $event->output;
    }
}
