<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\TwigRenderer;
use CraftCms\Cms\View\Events\CpTemplateRootsResolving;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TwigEngine;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Once;

afterEach(function () {
    app(Dispatcher::class)->forget(CpTemplateRootsResolving::class);
    Once::flush();
});

it('resolves the scoped template renderer for each render', function () {
    $resolutions = 0;

    app()->scoped(TwigRenderer::class, function () use (&$resolutions) {
        $resolutions++;

        $renderer = Mockery::mock(TwigRenderer::class);
        $renderer
            ->shouldReceive('renderPageTemplate')
            ->once()
            ->with('/fresh.twig', ['value' => $resolutions])
            ->andReturn("rendered-$resolutions");

        return $renderer;
    });

    $engine = new TwigEngine;
    $path = TemplateMode::get()->templatesPath().'/fresh.twig';

    expect($engine->get($path, ['value' => 1]))->toBe('rendered-1');

    app()->forgetScopedInstances();

    expect($engine->get($path, ['value' => 2]))->toBe('rendered-2');
});

it('maps plugin view paths to Craft template root names', function () {
    $root = storage_path('framework/testing/plugin-view-root-'.uniqid());
    File::ensureDirectoryExists("{$root}/tokens");
    File::put("{$root}/tokens/index.twig", 'Plugin tokens index');

    Once::flush();

    Event::listen(CpTemplateRootsResolving::class, function (CpTemplateRootsResolving $event) use ($root) {
        $event->roots['mcp'] = $root;
    });

    TemplateMode::set(TemplateMode::Cp);

    app()->scoped(TwigRenderer::class, function () {
        $renderer = Mockery::mock(TwigRenderer::class);
        $renderer
            ->shouldReceive('renderPageTemplate')
            ->once()
            ->with('mcp/tokens/index.twig', ['value' => 'test'])
            ->andReturn('rendered-plugin-view');

        return $renderer;
    });

    try {
        expect((new TwigEngine)->get("{$root}/tokens/index.twig", ['value' => 'test']))
            ->toBe('rendered-plugin-view');
    } finally {
        File::deleteDirectory($root);
    }
});
