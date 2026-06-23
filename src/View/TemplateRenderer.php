<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use CraftCms\Cms\Blade\BladeRenderer;
use CraftCms\Cms\Twig\Exceptions\TemplateLoaderException;
use CraftCms\Cms\Twig\TwigRenderer;
use CraftCms\Cms\View\Contracts\TemplateRendererInterface;
use CraftCms\Cms\View\Events\TemplateRenderersResolving;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\Collection;

use function CraftCms\Cms\t;

#[Scoped]
class TemplateRenderer
{
    /** @var ?Collection<TemplateRendererInterface> */
    private ?Collection $renderers = null;

    public function __construct(
        private readonly TemplateResolver $templateResolver,
    ) {}

    /**
     * @return Collection<TemplateRendererInterface>
     */
    private function renderers(): Collection
    {
        if (isset($this->renderers)) {
            return $this->renderers;
        }

        $renderers = [
            TwigRenderer::class,
            BladeRenderer::class,
        ];

        event($event = new TemplateRenderersResolving($renderers));

        return $this->renderers = collect($event->renderers)
            ->map(fn (string $renderer) => app($renderer));
    }

    private function rendererForFile(string $file): TemplateRendererInterface
    {
        return $this->renderers()->first(fn (TemplateRendererInterface $renderer) => $renderer->supports($file))
            ?? $this->renderers()->first();
    }

    public function renderTemplate(
        string $template,
        array $variables = [],
        ?TemplateMode $templateMode = null,
        bool $publicOnly = false,
    ): string {
        $resolvedTemplate = $this->templateResolver->resolve($template, $templateMode, $publicOnly);

        if ($resolvedTemplate === false) {
            throw new TemplateLoaderException($template, t('Unable to find the template “{template}”.', ['template' => $template]));
        }

        return $this->rendererForFile($resolvedTemplate)->renderTemplate($template, $variables, $templateMode, $resolvedTemplate);
    }

    public function renderPageTemplate(
        string $template,
        array $variables = [],
        ?TemplateMode $templateMode = null,
        bool $publicOnly = false,
    ): string {
        $resolvedTemplate = $this->templateResolver->resolve($template, $templateMode, $publicOnly);

        if ($resolvedTemplate === false) {
            throw new TemplateLoaderException($template, t('Unable to find the template “{template}”.', ['template' => $template]));
        }

        return $this->rendererForFile($resolvedTemplate)->renderPageTemplate($template, $variables, $templateMode, $resolvedTemplate);
    }
}
