<?php

declare(strict_types=1);

use CraftCms\Cms\Blade\BladeRenderer;
use CraftCms\Cms\View\TemplateHooks;
use CraftCms\Cms\View\TemplateMode;

beforeEach(function () {
    TemplateMode::set(TemplateMode::Site);
    app()->forgetScopedInstances();

    $this->renderer = app(BladeRenderer::class);
});

it('renders hook output with the current context', function () {
    app(TemplateHooks::class)->register('bladeGreeting', fn (array &$context, bool &$handled): string => 'Hello, '.$context['name']);

    $output = $this->renderer->renderString('@craftHook("bladeGreeting")', ['name' => 'Blade']);

    expect($output)->toBe('Hello, Blade');
});

it('extracts mutated hook context back into the Blade scope', function () {
    app(TemplateHooks::class)->register('bladeMutatingHook', function (array &$context, bool &$handled): string {
        $context['name'] = 'Mutated';

        return '';
    });

    $output = $this->renderer->renderString('@craftHook("bladeMutatingHook"){{ $name }}', ['name' => 'Original']);

    expect($output)->toBe('Mutated');
});
