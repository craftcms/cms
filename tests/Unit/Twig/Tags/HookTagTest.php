<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\TemplateRenderer;
use CraftCms\Cms\View\TemplateHooks;

beforeEach(function () {
    $this->renderer = app(TemplateRenderer::class);
    app()->forgetScopedInstances();
});

it('renders output from a registered hook handler', function () {
    app(TemplateHooks::class)->register('myHook', fn (array &$context, bool &$handled): string => '<p>Hook content</p>');

    $result = $this->renderer->renderString('{% hook "myHook" %}');

    expect(trim((string) $result))->toBe('<p>Hook content</p>');
});

it('renders empty string when no handlers are registered', function () {
    $result = $this->renderer->renderString('before{% hook "unregistered" %}after');

    expect(trim((string) $result))->toBe('beforeafter');
});

it('concatenates output from multiple handlers', function () {
    $hooks = app(TemplateHooks::class);
    $hooks->register('multi', fn (array &$context, bool &$handled) => 'A');
    $hooks->register('multi', fn (array &$context, bool &$handled) => 'B');

    $result = $this->renderer->renderString('{% hook "multi" %}');

    expect(trim((string) $result))->toBe('AB');
});

it('passes the template context to the hook handler', function () {
    app(TemplateHooks::class)->register('greeting', fn (array &$context, bool &$handled): string => 'Hello, '.($context['name'] ?? 'unknown'));

    $result = $this->renderer->renderString(
        '{% hook "greeting" %}',
        ['name' => 'World'],
    );

    expect(trim((string) $result))->toBe('Hello, World');
});

it('stops invoking handlers when handled flag is set', function () {
    $hooks = app(TemplateHooks::class);
    $hooks->register('handled', function (array &$context, bool &$handled): string {
        $handled = true;

        return 'First';
    });
    $hooks->register('handled', fn (array &$context, bool &$handled) => 'Second');

    $result = $this->renderer->renderString('{% hook "handled" %}');

    expect(trim((string) $result))->toBe('First');
});
