<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\Events\PageEnded;
use CraftCms\Cms\Twig\Events\PageStarting;
use CraftCms\Cms\View\BaseTemplateRenderer;
use CraftCms\Cms\View\Events\PageTemplateRendered;
use CraftCms\Cms\View\Events\PageTemplateRendering;
use CraftCms\Cms\View\Events\TemplateRendered;
use CraftCms\Cms\View\Events\TemplateRendering;
use CraftCms\Cms\View\PageLifecycle;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\Event;

class BaseTemplateRendererTestRenderer extends BaseTemplateRenderer
{
    public function supports(string $file): bool
    {
        return true;
    }

    public function renderTemplate(string $template, array $variables = [], ?TemplateMode $templateMode = null): string
    {
        return $this->renderInternal(
            $template,
            $variables,
            $templateMode,
            fn (string $template, array $variables) => "{$template}:{$variables['value']}",
        );
    }

    public function renderString(string $template, array $variables, TemplateMode $templateMode = TemplateMode::Site): string
    {
        return $template;
    }
}

beforeEach(function () {
    TemplateMode::set(TemplateMode::Site);
    app()->forgetScopedInstances();

    $this->renderer = new BaseTemplateRendererTestRenderer(app(PageLifecycle::class));
});

it('tracks render state and dispatches template events', function () {
    Event::fake([TemplateRendering::class, TemplateRendered::class]);

    expect($this->renderer->isRenderingTemplate())->toBeFalse();

    $output = $this->renderer->renderTemplate('example', ['value' => 'test'], TemplateMode::Cp);

    expect($output)->toBe('example:test');
    expect($this->renderer->isRenderingTemplate())->toBeFalse();

    Event::assertDispatched(fn (TemplateRendering $event) => $event->templateRenderer === BaseTemplateRendererTestRenderer::class
        && $event->template === 'example'
        && $event->variables === ['value' => 'test']
        && $event->templateMode === TemplateMode::Cp);
    Event::assertDispatched(fn (TemplateRendered $event) => $event->templateRenderer === BaseTemplateRendererTestRenderer::class
        && $event->template === 'example'
        && $event->output === 'example:test');
});

it('allows template events to cancel or modify rendering', function () {
    Event::listen(TemplateRendering::class, function (TemplateRendering $event) {
        $event->template = 'mutated';
        $event->variables['value'] = 'changed';
    });
    Event::listen(TemplateRendered::class, function (TemplateRendered $event) {
        $event->output = strtoupper($event->output);
    });

    expect($this->renderer->renderTemplate('example', ['value' => 'test']))
        ->toBe('MUTATED:CHANGED');

    Event::listen(TemplateRendering::class, function (TemplateRendering $event) {
        $event->isValid = false;
    });

    expect($this->renderer->renderTemplate('example', ['value' => 'test']))
        ->toBe('');
});

it('restores the template mode after rendering', function () {
    TemplateMode::set(TemplateMode::Cp);

    $this->renderer->renderTemplate('example', ['value' => 'test'], TemplateMode::Site);

    expect(TemplateMode::get())->toBe(TemplateMode::Cp);
});

it('wraps page templates in page lifecycle events', function () {
    $events = [];

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
        $event->output = "{$event->output}:done";
        $events[] = 'PageTemplateRendered';
    });

    expect($this->renderer->isRenderingPageTemplate())->toBeFalse();

    $output = $this->renderer->renderPageTemplate('example', ['value' => 'test'], TemplateMode::Site);

    expect($output)->toBe('example:page:done');
    expect($this->renderer->isRenderingPageTemplate())->toBeFalse();
    expect($events)->toBe([
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

    expect($this->renderer->renderPageTemplate('example', ['value' => 'test']))
        ->toBe('');
});
