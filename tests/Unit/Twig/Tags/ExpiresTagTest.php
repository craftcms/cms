<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Middleware\ApplyCacheControl;
use CraftCms\Cms\View\TemplateManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

beforeEach(function () {
    $this->manager = app(TemplateManager::class);
});

function renderAndApplyExpiresHeaders(string $template): Response
{
    app(TemplateManager::class)->renderString($template);

    return app(ApplyCacheControl::class)->handle(Request::create('foo'), fn () => new Response);
}

it('sets cache headers with duration', function () {
    $response = renderAndApplyExpiresHeaders('{% expires in 1 day %}');
    expect($response->headers->get('Cache-Control'))
        ->toContain('max-age=86400');
    expect($response->headers->hasCacheControlDirective('public'))->toBeTrue();
});

it('uses the last expiry tag duration', function (string $template, ?int $maxAge) {
    $response = renderAndApplyExpiresHeaders($template);

    expect($response->getMaxAge())->toBe($maxAge)
        ->and($response->headers->hasCacheControlDirective('no-cache'))->toBe($maxAge === null)
        ->and($response->headers->hasCacheControlDirective('no-store'))->toBe($maxAge === null)
        ->and($response->isCacheable())->toBe($maxAge !== null);
})->with([
    ['{% expires %}', null],
    ['{% expires %}{% expires in 2 hours %}', 7200],
    ['{% expires in 2 hours %}{% expires %}', null],
]);

it('supports hours', function () {
    $response = renderAndApplyExpiresHeaders('{% expires in 2 hours %}');

    expect($response->headers->get('Cache-Control'))->toContain('max-age=7200');
});

it('renders body content alongside setting headers', function () {
    $result = $this->manager->renderString('Before{% expires in 1 day %}After');

    expect(trim((string) $result))->toBe('BeforeAfter');
});
