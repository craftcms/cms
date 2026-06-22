<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\TemplateRenderer as TwigTemplateRenderer;
use CraftCms\Cms\Twig\TemplateResolver;
use CraftCms\Cms\View\BladeRenderer;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TemplateRenderer;

it('resolves templates using the requested template mode', function () {
    $blade = Mockery::mock(BladeRenderer::class);
    $resolver = Mockery::mock(TemplateResolver::class);
    $twig = Mockery::mock(TwigTemplateRenderer::class);

    $resolver
        ->shouldReceive('resolve')
        ->once()
        ->with('settings/example', TemplateMode::Cp, false)
        ->andReturn('/tmp/settings/example.twig');

    $twig
        ->shouldReceive('renderTemplate')
        ->once()
        ->with('settings/example', ['value' => 'test'], TemplateMode::Cp)
        ->andReturn('rendered-template');

    $renderer = new TemplateRenderer($blade, $resolver, $twig);

    expect($renderer->renderTemplate('settings/example', ['value' => 'test'], TemplateMode::Cp))
        ->toBe('rendered-template');
});

it('resolves page templates using the requested template mode and visibility', function () {
    $blade = Mockery::mock(BladeRenderer::class);
    $resolver = Mockery::mock(TemplateResolver::class);
    $twig = Mockery::mock(TwigTemplateRenderer::class);

    $resolver
        ->shouldReceive('resolve')
        ->once()
        ->with('articles/show', TemplateMode::Site, true)
        ->andReturn('/tmp/articles/show.twig');

    $twig
        ->shouldReceive('renderPageTemplate')
        ->once()
        ->with('articles/show', ['entry' => 'test'], TemplateMode::Site)
        ->andReturn('rendered-page-template');

    $renderer = new TemplateRenderer($blade, $resolver, $twig);

    expect($renderer->renderPageTemplate('articles/show', ['entry' => 'test'], TemplateMode::Site, publicOnly: true))
        ->toBe('rendered-page-template');
});
