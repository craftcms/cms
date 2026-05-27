<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\TemplateRenderer;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TwigEngine;

it('resolves the scoped template renderer for each render', function () {
    $resolutions = 0;

    app()->scoped(TemplateRenderer::class, function () use (&$resolutions) {
        $resolutions++;

        $renderer = Mockery::mock(TemplateRenderer::class);
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
