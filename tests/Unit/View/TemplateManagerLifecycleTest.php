<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\Events\PageEnded;
use CraftCms\Cms\Twig\Events\PageStarting;
use CraftCms\Cms\Twig\Exceptions\TemplateExitException;
use CraftCms\Cms\View\Contracts\TemplateRendererInterface;
use CraftCms\Cms\View\Events\PageTemplateRendered;
use CraftCms\Cms\View\Events\PageTemplateRendering;
use CraftCms\Cms\View\Events\TemplateRendered;
use CraftCms\Cms\View\Events\TemplateRendering;
use CraftCms\Cms\View\PageLifecycle;
use CraftCms\Cms\View\TemplateManager;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TemplateResolver;
use Illuminate\Support\Facades\Event;

class TemplateManagerLifecycleTestRenderer implements TemplateRendererInterface
{
    public Closure $renderTemplateUsing;

    public Closure $renderStringUsing;

    public function __construct()
    {
        $this->renderTemplateUsing = static fn (string $template, array $variables): string => "{$template}:{$variables['value']}";
        $this->renderStringUsing = static fn (string $template): string => $template;
    }

    public function supports(string $file): bool
    {
        return true;
    }

    public function renderTemplate(
        string $template,
        array $variables = [],
        ?TemplateMode $templateMode = null,
        ?string $resolvedTemplate = null,
    ): string {
        return ($this->renderTemplateUsing)($template, $variables, $templateMode, $resolvedTemplate);
    }

    public function renderString(
        string $template,
        array $variables = [],
        TemplateMode $templateMode = TemplateMode::Site,
    ): string {
        return ($this->renderStringUsing)($template, $variables, $templateMode);
    }
}

beforeEach(function () {
    TemplateMode::set(TemplateMode::Site);
    app()->forgetScopedInstances();

    $this->resolver = Mockery::mock(TemplateResolver::class);
    $this->resolver->shouldReceive('resolve')->byDefault()->andReturn('/tmp/example.test');
    $this->renderer = new TemplateManagerLifecycleTestRenderer;
    $this->manager = new TemplateManager(app(), $this->resolver, app(PageLifecycle::class));
    $renderer = $this->renderer;
    $this->manager->extend('test', static fn () => $renderer);
});

it('tracks render state and dispatches template events', function () {
    $wasRendering = null;
    $this->renderer->renderTemplateUsing = function (string $template, array $variables) use (&$wasRendering): string {
        $wasRendering = $this->manager->isRenderingTemplate();

        return "{$template}:{$variables['value']}";
    };

    Event::fake([TemplateRendering::class, TemplateRendered::class]);

    expect($this->manager->isRenderingTemplate())->toBeFalse();

    $output = $this->manager->renderTemplate('example', ['value' => 'test'], TemplateMode::Cp, renderer: 'test');

    expect($output)->toBe('example:test')
        ->and($wasRendering)->toBeTrue()
        ->and($this->manager->isRenderingTemplate())->toBeFalse();

    Event::assertDispatched(fn (TemplateRendering $event) => $event->template === 'example'
        && $event->variables === ['value' => 'test']
        && $event->templateMode === TemplateMode::Cp);
    Event::assertDispatched(fn (TemplateRendered $event) => $event->rendererName === 'test'
        && $event->template === 'example'
        && $event->output === 'example:test');
});

it('allows template events to mutate input and output', function () {
    Event::listen(TemplateRendering::class, function (TemplateRendering $event) {
        $event->template = 'mutated';
        $event->variables['value'] = 'changed';
        $event->templateMode = TemplateMode::Cp;
    });

    $this->resolver
        ->shouldReceive('resolve')
        ->once()
        ->with('mutated', TemplateMode::Cp, false)
        ->andReturn('/tmp/mutated.test');

    Event::listen(TemplateRendered::class, function (TemplateRendered $event) {
        $event->output = strtoupper($event->output);
    });

    expect($this->manager->renderTemplate('example', ['value' => 'test'], renderer: 'test'))
        ->toBe('MUTATED:CHANGED');
});

it('allows before events to cancel before template resolution', function () {
    Event::listen(TemplateRendering::class, function (TemplateRendering $event) {
        $event->isValid = false;
    });

    $this->resolver->shouldNotReceive('resolve');

    expect($this->manager->renderTemplate('example', ['value' => 'test'], renderer: 'test'))
        ->toBe('');
});

it('restores template mode and state when rendering throws', function () {
    TemplateMode::set(TemplateMode::Cp);
    $this->renderer->renderTemplateUsing = function (): never {
        expect($this->manager->isRenderingTemplate())->toBeTrue()
            ->and(TemplateMode::get())->toBe(TemplateMode::Site);

        throw new RuntimeException('render failed');
    };

    expect(fn () => $this->manager->renderTemplate('example', templateMode: TemplateMode::Site, renderer: 'test'))
        ->toThrow(RuntimeException::class, 'render failed');
    expect($this->manager->isRenderingTemplate())->toBeFalse()
        ->and(TemplateMode::get())->toBe(TemplateMode::Cp);
});

it('tracks nested rendering state without clearing the outer render', function () {
    $states = [];
    $this->renderer->renderStringUsing = function (string $template) use (&$states): string {
        $states[] = $this->manager->isRenderingTemplate();

        return strtoupper($template);
    };
    $this->renderer->renderTemplateUsing = function () use (&$states): string {
        $states[] = $this->manager->isRenderingTemplate();
        $output = $this->manager->renderString('nested', renderer: 'test');
        $states[] = $this->manager->isRenderingTemplate();

        return $output;
    };

    expect($this->manager->renderTemplate('outer', renderer: 'test'))->toBe('NESTED')
        ->and($states)->toBe([true, true, true])
        ->and($this->manager->isRenderingTemplate())->toBeFalse();
});

it('wraps page templates in the established lifecycle event order', function () {
    $events = [];
    $pageWasRendering = null;
    $this->renderer->renderTemplateUsing = function (string $template, array $variables) use (&$pageWasRendering): string {
        $pageWasRendering = $this->manager->isRenderingPageTemplate();

        return "{$template}:{$variables['value']}";
    };

    Event::listen(PageTemplateRendering::class, function (PageTemplateRendering $event) use (&$events) {
        $event->variables['value'] = 'page';
        $events[] = 'PageTemplateRendering';
    });
    Event::listen(PageStarting::class, function () use (&$events) {
        $events[] = 'PageStarting';
    });
    Event::listen(TemplateRendering::class, function () use (&$events) {
        $events[] = 'TemplateRendering';
    });
    Event::listen(TemplateRendered::class, function () use (&$events) {
        $events[] = 'TemplateRendered';
    });
    Event::listen(PageEnded::class, function () use (&$events) {
        $events[] = 'PageEnded';
    });
    Event::listen(PageTemplateRendered::class, function (PageTemplateRendered $event) use (&$events) {
        expect($event->rendererName)->toBe('test');
        $event->output = "{$event->output}:done";
        $events[] = 'PageTemplateRendered';
    });

    $output = $this->manager->renderPageTemplate('example', ['value' => 'test'], TemplateMode::Site, renderer: 'test');

    expect($output)->toBe('example:page:done')
        ->and($pageWasRendering)->toBeTrue()
        ->and($this->manager->isRenderingPageTemplate())->toBeFalse()
        ->and($events)->toBe([
            'PageTemplateRendering',
            'PageStarting',
            'TemplateRendering',
            'TemplateRendered',
            'PageEnded',
            'PageTemplateRendered',
        ]);
});

it('returns empty output when page rendering is cancelled', function () {
    Event::listen(PageTemplateRendering::class, function (PageTemplateRendering $event) {
        $event->isValid = false;
    });

    $this->resolver->shouldNotReceive('resolve');

    expect($this->manager->renderPageTemplate('example', ['value' => 'test'], renderer: 'test'))
        ->toBe('');
});

it('reports the requested renderer when inner template rendering is cancelled', function () {
    Event::listen(TemplateRendering::class, function (TemplateRendering $event) {
        $event->isValid = false;
    });

    Event::listen(PageTemplateRendered::class, function (PageTemplateRendered $event) {
        expect($event->rendererName)->toBe('test');
    });

    $this->resolver->shouldNotReceive('resolve');

    expect($this->manager->renderPageTemplate('example', renderer: 'test'))->toBe('');
});

it('reports the selected renderer when a page template exits early', function () {
    $this->renderer->renderTemplateUsing = function (): never {
        throw new TemplateExitException;
    };

    Event::listen(PageTemplateRendered::class, function (PageTemplateRendered $event) {
        expect($event->rendererName)->toBe('test');
    });

    expect($this->manager->renderPageTemplate('example', renderer: 'test'))->toBe('');
});

it('restores page state when page rendering throws', function () {
    $this->renderer->renderTemplateUsing = function (): never {
        expect($this->manager->isRenderingPageTemplate())->toBeTrue();

        throw new RuntimeException('page failed');
    };

    expect(fn () => $this->manager->renderPageTemplate('example', renderer: 'test'))
        ->toThrow(RuntimeException::class, 'page failed');
    expect($this->manager->isRenderingPageTemplate())->toBeFalse();
});

it('renders inline strings without file lifecycle events', function () {
    Event::fake([
        PageTemplateRendering::class,
        PageTemplateRendered::class,
        TemplateRendering::class,
        TemplateRendered::class,
    ]);

    TemplateMode::set(TemplateMode::Cp);
    $modeDuringRender = null;
    $wasRendering = null;
    $this->renderer->renderStringUsing = function (string $template) use (&$modeDuringRender, &$wasRendering): string {
        $modeDuringRender = TemplateMode::get();
        $wasRendering = $this->manager->isRenderingTemplate();

        return strtoupper($template);
    };

    expect($this->manager->renderString('inline', templateMode: TemplateMode::Site, renderer: 'test'))->toBe('INLINE')
        ->and($modeDuringRender)->toBe(TemplateMode::Site)
        ->and($wasRendering)->toBeTrue()
        ->and($this->manager->isRenderingTemplate())->toBeFalse()
        ->and(TemplateMode::get())->toBe(TemplateMode::Cp);

    Event::assertNotDispatched(TemplateRendering::class);
    Event::assertNotDispatched(TemplateRendered::class);
    Event::assertNotDispatched(PageTemplateRendering::class);
    Event::assertNotDispatched(PageTemplateRendered::class);
});
