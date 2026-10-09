<?php

declare(strict_types=1);

use CraftCms\Cms\Blade\BladeRenderer;
use CraftCms\Cms\Twig\Contracts\TwigRendererInterface;
use CraftCms\Cms\Twig\Exceptions\TemplateLoaderException;
use CraftCms\Cms\Twig\TwigRenderer;
use CraftCms\Cms\View\Contracts\TemplateRendererInterface;
use CraftCms\Cms\View\Events\TemplateRendered;
use CraftCms\Cms\View\Events\TemplateRendering;
use CraftCms\Cms\View\PageLifecycle;
use CraftCms\Cms\View\TemplateEngine;
use CraftCms\Cms\View\TemplateManager;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TemplateResolver;
use Illuminate\Support\Facades\Event;

class TemplateManagerTestRenderer implements TemplateRendererInterface
{
    public array $renderedTemplates = [];

    public array $renderedStrings = [];

    public function __construct(
        private readonly array $supportedFiles = [],
        private readonly string $output = 'rendered',
    ) {}

    public function supports(string $file): bool
    {
        return in_array($file, $this->supportedFiles, true);
    }

    public function renderTemplate(
        string $template,
        array $variables = [],
        ?TemplateMode $templateMode = null,
        ?string $resolvedTemplate = null,
    ): string {
        $this->renderedTemplates[] = [$template, $variables, $templateMode, $resolvedTemplate];

        return $this->output;
    }

    public function renderString(
        string $template,
        array $variables = [],
        TemplateMode $templateMode = TemplateMode::Site,
    ): string {
        $this->renderedStrings[] = [$template, $variables, $templateMode];

        return $this->output;
    }
}

class TemplateManagerTestTwigRenderer extends TemplateManagerTestRenderer implements TwigRendererInterface
{
    public function renderSandboxedTemplate(
        string $template,
        array $variables = [],
        ?TemplateMode $templateMode = null,
        ?string $resolvedTemplate = null,
    ): string {
        return $this->renderTemplate($template, $variables, $templateMode, $resolvedTemplate);
    }

    #[Override]
    public function renderString(
        string $template,
        array $variables = [],
        TemplateMode $templateMode = TemplateMode::Site,
        bool $escapeHtml = false,
    ): string {
        return parent::renderString($template, $variables, $templateMode);
    }

    public function renderSandboxedString(
        string $template,
        array $variables = [],
        TemplateMode $templateMode = TemplateMode::Site,
        bool $escapeHtml = false,
    ): string {
        return $this->renderString($template, $variables, $templateMode);
    }

    public function renderObjectTemplate(
        string $template,
        mixed $object,
        array $variables = [],
        TemplateMode $templateMode = TemplateMode::Site,
        string|false $escaperStrategy = false,
    ): string {
        return $this->renderString($template, $variables, $templateMode);
    }

    public function renderSandboxedObjectTemplate(
        string $template,
        mixed $object,
        array $variables = [],
        TemplateMode $templateMode = TemplateMode::Site,
    ): string {
        return $this->renderString($template, $variables, $templateMode);
    }

    public function normalizeObjectTemplate(string $template): string
    {
        return $template;
    }
}

beforeEach(function () {
    TemplateMode::set(TemplateMode::Site);
    app()->forgetScopedInstances();

    $this->resolver = Mockery::mock(TemplateResolver::class);
    $this->manager = new TemplateManager(app(), $this->resolver, app(PageLifecycle::class));
});

it('resolves and caches built-in renderers by default, enum, and string names', function () {
    expect($this->manager->renderer())->toBeInstanceOf(TwigRenderer::class)
        ->and($this->manager->renderer(TemplateEngine::Twig))->toBe($this->manager->renderer())
        ->and($this->manager->renderer(TemplateEngine::Blade))->toBeInstanceOf(BladeRenderer::class)
        ->and($this->manager->renderer('blade'))->toBe($this->manager->renderer(TemplateEngine::Blade));
});

it('throws for unknown and invalid renderers', function () {
    expect(fn () => $this->manager->renderer('missing'))
        ->toThrow(InvalidArgumentException::class);

    $this->manager->extend('invalid', static fn () => new stdClass);

    expect(fn () => $this->manager->renderer('invalid'))
        ->toThrow(UnexpectedValueException::class);
});

it('requires the registered Twig renderer to implement the Twig contract', function () {
    $this->manager->extend(TemplateEngine::Twig, static fn () => new TemplateManagerTestRenderer);

    expect(fn () => $this->manager->renderer(TemplateEngine::Twig))
        ->toThrow(UnexpectedValueException::class);
});

it('keeps Laravel manager caching semantics when a resolved renderer is replaced', function () {
    $first = new TemplateManagerTestRenderer;
    $second = new TemplateManagerTestRenderer;

    $this->manager->extend('custom', static fn () => $first);

    expect($this->manager->renderer('custom'))->toBe($first);

    $this->manager->extend('custom', static fn () => $second);

    expect($this->manager->renderer('custom'))->toBe($first);

    $this->manager->forgetRenderers();

    expect($this->manager->renderer('custom'))->toBe($second);
});

it('replays the latest replacement in new manager scopes', function () {
    $second = new TemplateManagerTestRenderer;
    $manager = app(TemplateManager::class);

    $manager->extend('custom', static fn () => new TemplateManagerTestRenderer);
    $manager->extend('custom', static fn () => $second);

    app()->forgetScopedInstances();

    expect(app(TemplateManager::class)->renderer('custom'))->toBe($second);
});

it('selects the first supporting renderer and appends custom renderers', function () {
    $first = new TemplateManagerTestRenderer(['/tmp/example.custom'], 'first');
    $second = new TemplateManagerTestRenderer(['/tmp/example.custom'], 'second');

    $this->manager->extend('first', static fn () => $first);
    $this->manager->extend('second', static fn () => $second);

    $this->resolver
        ->shouldReceive('resolve')
        ->once()
        ->with('example', TemplateMode::Cp, false)
        ->andReturn('/tmp/example.custom');

    expect($this->manager->renderTemplate('example', ['value' => 'test'], TemplateMode::Cp))
        ->toBe('first')
        ->and($first->renderedTemplates)
        ->toBe([['example', ['value' => 'test'], TemplateMode::Cp, '/tmp/example.custom']])
        ->and($second->renderedTemplates)->toBe([]);
});

it('keeps a replaced renderer in its existing automatic-selection position', function () {
    $original = new TemplateManagerTestRenderer(['/tmp/example.custom'], 'original');
    $replacement = new TemplateManagerTestRenderer(['/tmp/example.custom'], 'replacement');
    $second = new TemplateManagerTestRenderer(['/tmp/example.custom'], 'second');

    $this->manager->extend('first', static fn () => $original);
    $this->manager->extend('second', static fn () => $second);
    $this->manager->extend('first', static fn () => $replacement);

    $this->resolver->shouldReceive('resolve')->andReturn('/tmp/example.custom');

    expect($this->manager->renderTemplate('example'))->toBe('replacement');
});

it('falls back to Twig for unmatched configured extensions', function () {
    $twig = new TemplateManagerTestTwigRenderer([], 'twig-fallback');
    $this->manager->extend(TemplateEngine::Twig, static fn () => $twig);
    $this->resolver->shouldReceive('resolve')->andReturn('/tmp/example.txt');

    expect($this->manager->renderTemplate('example'))->toBe('twig-fallback');
});

it('honors an explicitly requested renderer without requiring supports', function () {
    $renderer = new TemplateManagerTestRenderer([], 'explicit');
    $this->manager->extend('custom', static fn () => $renderer);
    $this->resolver->shouldReceive('resolve')->andReturn('/tmp/example.twig');

    expect($this->manager->renderTemplate('example', renderer: 'custom'))->toBe('explicit');
});

it('selects the renderer after before-event template mutation', function () {
    $twig = new TemplateManagerTestTwigRenderer(['/tmp/original.twig'], 'twig');
    $blade = new TemplateManagerTestRenderer(['/tmp/mutated.blade.php'], 'blade');

    $this->manager->extend(TemplateEngine::Twig, static fn () => $twig);
    $this->manager->extend(TemplateEngine::Blade, static fn () => $blade);

    Event::listen(TemplateRendering::class, function (TemplateRendering $event) {
        $event->template = 'mutated';
    });

    $this->resolver
        ->shouldReceive('resolve')
        ->once()
        ->with('mutated', TemplateMode::Site, false)
        ->andReturn('/tmp/mutated.blade.php');

    $renderedRendererName = null;
    Event::listen(TemplateRendered::class, function (TemplateRendered $event) use (&$renderedRendererName) {
        $renderedRendererName = $event->rendererName;
    });

    expect($this->manager->renderTemplate('original'))->toBe('blade')
        ->and($renderedRendererName)->toBe(TemplateEngine::Blade->value);
});

it('throws a template loader exception for missing templates', function () {
    $this->resolver
        ->shouldReceive('resolve')
        ->once()
        ->with('missing/template', TemplateMode::Cp, false)
        ->andReturnFalse();

    expect(fn () => $this->manager->renderTemplate('missing/template', templateMode: TemplateMode::Cp))
        ->toThrow(TemplateLoaderException::class);
});

it('passes public-only resolution through unchanged', function () {
    $renderer = new TemplateManagerTestRenderer(['/tmp/example.custom'], 'public');
    $this->manager->extend('custom', static fn () => $renderer);
    $this->resolver
        ->shouldReceive('resolve')
        ->once()
        ->with('example', TemplateMode::Site, true)
        ->andReturn('/tmp/example.custom');

    expect($this->manager->renderTemplate('example', publicOnly: true))->toBe('public');
});
