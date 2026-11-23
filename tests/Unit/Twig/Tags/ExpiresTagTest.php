<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Middleware\SetHeaders;
use CraftCms\Cms\View\TemplateManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

beforeEach(function () {
    $this->manager = app(TemplateManager::class);
});

function renderAndApplyExpiresHeaders(string $template): Response
{
    app(TemplateManager::class)->renderString($template);

    return app(SetHeaders::class)->handle(Request::create('foo'), fn () => new Response);
}

it('sets cache headers with duration', function () {
    $response = renderAndApplyExpiresHeaders('{% expires in 1 day %}');
    $now = now();
    $duration = (int) $now->diffInSeconds((clone $now)->add(1, 'day'));

    expect($response->headers->get('Cache-Control'))->toBe("max-age=$duration, public")
        ->and($response->headers->has('Pragma'))->toBeFalse()
        ->and($response->headers->has('Expires'))->toBeFalse();
});

it('sets no-cache headers with no duration', function () {
    $response = renderAndApplyExpiresHeaders('{% expires %}');

    expect($response->headers->get('Cache-Control'))->toContain('no-cache');
});

it('supports hours', function () {
    $response = renderAndApplyExpiresHeaders('{% expires in 2 hours %}');

    expect($response->headers->get('Cache-Control'))->toContain('max-age=7200');
});

it('renders body content alongside setting headers', function () {
    $result = $this->manager->renderString('Before{% expires in 1 day %}After');

    expect(trim((string) $result))->toBe('BeforeAfter');
});

it('queues cache directives through the ResponseHeaders global', function () {
    $response = renderAndApplyExpiresHeaders('{% do ResponseHeaders.cacheControl.setPublic().setSharedMaxAge(500).setStaleWhileRevalidate(60) %}');

    expect($response->headers->get('Cache-Control'))->toBe('public, s-maxage=500, stale-while-revalidate=60');
});
