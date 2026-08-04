<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use Closure;
use CraftCms\Cms\Blade\BladeRenderer;
use CraftCms\Cms\Twig\Contracts\TwigRendererInterface;
use CraftCms\Cms\Twig\Exceptions\TemplateLoaderException;
use CraftCms\Cms\Twig\TwigRenderer;
use CraftCms\Cms\View\Contracts\TemplateRendererInterface;
use CraftCms\Cms\View\Events\PageTemplateRendered;
use CraftCms\Cms\View\Events\PageTemplateRendering;
use CraftCms\Cms\View\Events\TemplateRendered;
use CraftCms\Cms\View\Events\TemplateRendering;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Manager;
use InvalidArgumentException;
use UnexpectedValueException;

use function CraftCms\Cms\t;
use function Illuminate\Support\enum_value;

#[Scoped]
class TemplateManager extends Manager
{
    private ?string $renderingTemplate = null;

    private bool $isRenderingPageTemplate = false;

    public function __construct(
        Container $container,
        private readonly TemplateResolver $templateResolver,
        private readonly PageLifecycle $pageLifecycle,
    ) {
        parent::__construct($container);
    }

    public function getDefaultDriver(): string
    {
        return TemplateEngine::Twig->value;
    }

    public function renderer(TemplateEngine|string|null $renderer = null): TemplateRendererInterface
    {
        return $this->driver($renderer);
    }

    /**
     * @param  TemplateEngine|string|null  $driver
     */
    #[\Override]
    public function driver($driver = null): TemplateRendererInterface
    {
        $resolvedRenderer = parent::driver($driver);
        $rendererName = $this->normalizeRendererName($driver) ?? $this->getDefaultDriver();

        if (! $resolvedRenderer instanceof TemplateRendererInterface) {
            throw new UnexpectedValueException("Template renderer [{$rendererName}] must implement ".TemplateRendererInterface::class.'.');
        }

        if ($rendererName === TemplateEngine::Twig->value && ! $resolvedRenderer instanceof TwigRendererInterface) {
            throw new UnexpectedValueException('The Twig renderer must implement '.TwigRendererInterface::class.'.');
        }

        return $resolvedRenderer;
    }

    /**
     * @param  TemplateEngine|string  $renderer
     */
    #[\Override]
    public function extend($renderer, Closure $callback): static
    {
        $rendererName = $this->normalizeRendererName($renderer);

        if ($rendererName === null) {
            throw new InvalidArgumentException('A template renderer name is required.');
        }

        $this->extendCurrentScope($rendererName, $callback);

        $this->container->afterResolving(
            self::class,
            static fn (self $manager) => $manager->extendCurrentScope($rendererName, $callback),
        );

        return $this;
    }

    public function forgetRenderers(): static
    {
        $this->forgetDrivers();

        return $this;
    }

    public function isRenderingTemplate(): bool
    {
        return isset($this->renderingTemplate);
    }

    public function isRenderingPageTemplate(): bool
    {
        return $this->isRenderingPageTemplate;
    }

    /** @param array<string, mixed> $variables */
    public function renderTemplate(
        string $template,
        array $variables = [],
        ?TemplateMode $templateMode = null,
        bool $publicOnly = false,
        TemplateEngine|string|null $renderer = null,
    ): string {
        return $this->renderFileTemplate($template, $variables, $templateMode, $publicOnly, $renderer);
    }

    /** @param array<string, mixed> $variables */
    public function renderSandboxedTemplate(
        string $template,
        array $variables = [],
        ?TemplateMode $templateMode = null,
        bool $publicOnly = false,
    ): string {
        return $this->renderFileTemplate(
            $template,
            $variables,
            $templateMode,
            $publicOnly,
            TemplateEngine::Twig,
            sandboxed: true,
        );
    }

    /** @param array<string, mixed> $variables */
    public function renderPageTemplate(
        string $template,
        array $variables = [],
        ?TemplateMode $templateMode = null,
        bool $publicOnly = false,
        TemplateEngine|string|null $renderer = null,
    ): string {
        $templateMode ??= TemplateMode::get();

        event($event = new PageTemplateRendering($template, $variables, $templateMode));

        if (! $event->isValid) {
            return '';
        }

        $template = $event->template;
        $variables = $event->variables;
        $templateMode = $event->templateMode;
        $isRenderingPageTemplate = $this->isRenderingPageTemplate;
        $this->isRenderingPageTemplate = true;
        $rendererName = $this->normalizeRendererName($renderer) ?? $this->getDefaultDriver();

        try {
            $output = $this->pageLifecycle->wrap(function () use (
                &$rendererName,
                $template,
                $variables,
                $templateMode,
                $publicOnly,
                $renderer,
            ): string {
                return $this->renderFileTemplate(
                    $template,
                    $variables,
                    $templateMode,
                    $publicOnly,
                    $renderer,
                    rendererSelected: static function (string $selectedRenderer) use (&$rendererName): void {
                        $rendererName = $selectedRenderer;
                    },
                );
            });
        } finally {
            $this->isRenderingPageTemplate = $isRenderingPageTemplate;
        }

        event($event = new PageTemplateRendered(
            $rendererName,
            $template,
            $variables,
            $templateMode,
            $output,
        ));

        return $event->output;
    }

    /** @param array<string, mixed> $variables */
    public function renderString(
        string $template,
        array $variables = [],
        TemplateMode $templateMode = TemplateMode::Site,
        TemplateEngine|string|null $renderer = null,
    ): string {
        $resolvedRenderer = $this->renderer($renderer);

        return $this->withRenderingState(
            'string:'.$template,
            $templateMode,
            fn () => $resolvedRenderer->renderString($template, $variables, $templateMode),
        );
    }

    /** @param array<string, mixed> $variables */
    public function renderTwigString(
        string $template,
        array $variables = [],
        TemplateMode $templateMode = TemplateMode::Site,
        bool $escapeHtml = false,
    ): string {
        $renderer = $this->twigRenderer();

        return $this->withRenderingState(
            'string:'.$template,
            $templateMode,
            fn () => $renderer->renderString($template, $variables, $templateMode, $escapeHtml),
        );
    }

    /** @param array<string, mixed> $variables */
    public function renderSandboxedString(
        string $template,
        array $variables = [],
        TemplateMode $templateMode = TemplateMode::Site,
        bool $escapeHtml = false,
    ): string {
        $renderer = $this->twigRenderer();

        return $this->withRenderingState(
            'string:'.$template,
            $templateMode,
            fn () => $renderer->renderSandboxedString($template, $variables, $templateMode, $escapeHtml),
        );
    }

    /** @param array<string, mixed> $variables */
    public function renderObjectTemplate(
        string $template,
        mixed $object,
        array $variables = [],
        TemplateMode $templateMode = TemplateMode::Site,
        string|false $escaperStrategy = false,
    ): string {
        $renderer = $this->twigRenderer();

        return $this->withRenderingState(
            'string:'.$template,
            $templateMode,
            fn () => $renderer->renderObjectTemplate($template, $object, $variables, $templateMode, $escaperStrategy),
        );
    }

    /** @param array<string, mixed> $variables */
    public function renderSandboxedObjectTemplate(
        string $template,
        mixed $object,
        array $variables = [],
        TemplateMode $templateMode = TemplateMode::Site,
    ): string {
        $renderer = $this->twigRenderer();

        return $this->withRenderingState(
            'string:'.$template,
            $templateMode,
            fn () => $renderer->renderSandboxedObjectTemplate($template, $object, $variables, $templateMode),
        );
    }

    public function normalizeObjectTemplate(string $template): string
    {
        return $this->twigRenderer()->normalizeObjectTemplate($template);
    }

    protected function createTwigDriver(): TwigRendererInterface
    {
        return $this->container->make(TwigRenderer::class);
    }

    protected function createBladeDriver(): TemplateRendererInterface
    {
        return $this->container->make(BladeRenderer::class);
    }

    /** @param array<string, mixed> $variables */
    private function renderFileTemplate(
        string $template,
        array $variables,
        ?TemplateMode $templateMode,
        bool $publicOnly,
        TemplateEngine|string|null $renderer,
        bool $sandboxed = false,
        ?Closure $rendererSelected = null,
    ): string {
        $templateMode ??= TemplateMode::get();

        event($event = new TemplateRendering($template, $variables, $templateMode));

        if (! $event->isValid) {
            return '';
        }

        $template = $event->template;
        $variables = $event->variables;
        $templateMode = $event->templateMode;
        $resolvedTemplate = $this->templateResolver->resolve($template, $templateMode, $publicOnly);

        if ($resolvedTemplate === false) {
            throw new TemplateLoaderException($template, t('Unable to find the template “{template}”.', ['template' => $template]));
        }

        [$rendererName, $resolvedRenderer] = $this->rendererForFile($resolvedTemplate, $renderer);
        $rendererSelected?->__invoke($rendererName);

        Log::debug("Rendering template: {$template}", ['method' => __METHOD__, 'renderer' => $rendererName]);

        $output = $this->withRenderingState(
            $template,
            $templateMode,
            fn () => $sandboxed
                ? $this->twigRenderer($resolvedRenderer)->renderSandboxedTemplate($template, $variables, $templateMode, $resolvedTemplate)
                : $resolvedRenderer->renderTemplate($template, $variables, $templateMode, $resolvedTemplate),
        );

        event($event = new TemplateRendered($rendererName, $template, $variables, $templateMode, $output));

        return $event->output;
    }

    /**
     * @return array{string, TemplateRendererInterface}
     */
    private function rendererForFile(
        string $file,
        TemplateEngine|string|null $renderer,
    ): array {
        if ($renderer !== null) {
            $rendererName = $this->normalizeRendererName($renderer) ?? $this->getDefaultDriver();

            return [$rendererName, $this->renderer($renderer)];
        }

        $rendererNames = array_unique([
            TemplateEngine::Twig->value,
            TemplateEngine::Blade->value,
            ...array_map(strval(...), array_keys($this->customCreators)),
        ]);

        foreach ($rendererNames as $rendererName) {
            $resolvedRenderer = $this->renderer($rendererName);

            if ($resolvedRenderer->supports($file)) {
                return [$rendererName, $resolvedRenderer];
            }
        }

        return [$this->getDefaultDriver(), $this->renderer()];
    }

    private function twigRenderer(?TemplateRendererInterface $renderer = null): TwigRendererInterface
    {
        $renderer ??= $this->renderer(TemplateEngine::Twig);

        if (! $renderer instanceof TwigRendererInterface) {
            throw new UnexpectedValueException('The Twig renderer must implement '.TwigRendererInterface::class.'.');
        }

        return $renderer;
    }

    private function withRenderingState(string $template, TemplateMode $templateMode, callable $render): string
    {
        $previousTemplateMode = TemplateMode::get();
        $previousTemplate = $this->renderingTemplate;

        TemplateMode::set($templateMode);
        $this->renderingTemplate = $template;

        try {
            return $render();
        } finally {
            $this->renderingTemplate = $previousTemplate;
            TemplateMode::set($previousTemplateMode);
        }
    }

    private function normalizeRendererName(mixed $renderer): ?string
    {
        return enum_value($renderer) ?: null;
    }

    private function extendCurrentScope(string $renderer, Closure $callback): void
    {
        parent::extend($renderer, $callback);
    }
}
