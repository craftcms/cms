<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Twig\TemplateRenderer as TwigTemplateRenderer;
use CraftCms\Cms\Twig\TemplateResolver;
use Illuminate\Container\Attributes\Scoped;
use RuntimeException;

#[Scoped]
readonly class TemplateRenderer
{
    public function __construct(
        private BladeRenderer $blade,
        private TemplateResolver $templateResolver,
        private TwigTemplateRenderer $twig,
    ) {}

    public function renderTemplate(
        string $template,
        array $variables = [],
        ?TemplateMode $templateMode = null,
        bool $publicOnly = false,
    ): string {
        $resolvedTemplate = $this->templateResolver->resolve($template, $templateMode, $publicOnly);

        if ($resolvedTemplate === false) {
            throw new RuntimeException("Template {$template} not found.");
        }

        return $this->renderResolvedTemplate($template, $resolvedTemplate, $variables, $templateMode);
    }

    public function renderPageTemplate(
        string $template,
        array $variables = [],
        ?TemplateMode $templateMode = null,
        bool $publicOnly = false,
    ): string {
        $resolvedTemplate = $this->templateResolver->resolve($template, $templateMode, $publicOnly);

        if ($resolvedTemplate === false) {
            throw new RuntimeException("Template {$template} not found.");
        }

        return $this->renderResolvedPageTemplate($template, $resolvedTemplate, $variables, $templateMode);
    }

    public function renderResolvedTemplate(
        string $template,
        string $resolvedTemplate,
        array $variables = [],
        ?TemplateMode $templateMode = null,
    ): string {
        if ($this->isBlade($resolvedTemplate)) {
            return $this->blade->renderFile($resolvedTemplate, $variables, $templateMode, $template);
        }

        return $this->twig->renderTemplate($template, $variables, $templateMode);
    }

    public function renderResolvedPageTemplate(
        string $template,
        string $resolvedTemplate,
        array $variables = [],
        ?TemplateMode $templateMode = null,
    ): string {
        if ($this->isBlade($resolvedTemplate)) {
            return $this->blade->renderPageFile($resolvedTemplate, $variables, $templateMode, $template);
        }

        return $this->twig->renderPageTemplate($template, $variables, $templateMode);
    }

    private function isBlade(string $path): bool
    {
        return Str::endsWith($path, '.blade.php');
    }
}
