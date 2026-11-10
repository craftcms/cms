<?php

declare(strict_types=1);

use CraftCms\Cms\Blade\BladeRenderer;
use CraftCms\Cms\Http\Middleware\ApplyCacheControl;
use CraftCms\Cms\Http\Middleware\SetHeaders;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

beforeEach(function () {
    TemplateMode::set(TemplateMode::Site);
    app()->forgetScopedInstances();

    $this->renderer = app(BladeRenderer::class);
});

function renderBladeAndApplyHeaders(string $template): Response
{
    app(BladeRenderer::class)->renderString($template);

    return app(ApplyCacheControl::class)->handle(
        Request::create('foo'),
        fn ($request) => app(SetHeaders::class)->handle($request, fn () => new Response),
    );
}

it('sets a response header', function () {
    $response = renderBladeAndApplyHeaders('@craftHeader("X-Custom: test-value")');

    expect($response->headers->get('X-Custom'))->toBe('test-value');
});

it('isolates response headers to the request scope', function () {
    app(BladeRenderer::class)->renderString('@craftHeader("X-Custom: test-value")');

    app()->forgetScopedInstances();

    $response = app(SetHeaders::class)->handle(Request::create('foo'), fn () => new Response);

    expect($response->headers->has('X-Custom'))->toBeFalse();
});

it('sets cache headers with duration', function () {
    $response = renderBladeAndApplyHeaders('@craftExpires(7200)');

    expect($response->headers->get('Cache-Control'))->toContain('max-age=7200')
        ->and($response->headers->hasCacheControlDirective('public'))->toBeTrue();
});

it('uses the last expiry directive duration', function (string $template, ?int $maxAge) {
    $response = renderBladeAndApplyHeaders($template);

    expect($response->getMaxAge())->toBe($maxAge)
        ->and($response->headers->hasCacheControlDirective('no-cache'))->toBe($maxAge === null)
        ->and($response->headers->hasCacheControlDirective('no-store'))->toBe($maxAge === null)
        ->and($response->isCacheable())->toBe($maxAge !== null);
})->with([
    ['@craftExpires(0) @craftExpires(7200)', 7200],
    ['@craftExpires(7200) @craftExpires(0)', null],
]);
