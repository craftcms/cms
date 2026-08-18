<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Middleware\ShowBrokenImage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->middleware = app(ShowBrokenImage::class);
    $this->imagePath = storage_path('framework/testing/broken-image-test.svg');

    File::ensureDirectoryExists(dirname($this->imagePath));
    File::put($this->imagePath, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

    Cms::config()->brokenImagePath = $this->imagePath;
});

afterEach(function () {
    File::delete($this->imagePath);
});

it('passes through non-response values', function () {
    $request = Request::create('/missing');

    $result = $this->middleware->handle($request, fn () => 'not-a-response');

    expect($result)->toBe('not-a-response');
});

it('passes through non-404 responses', function () {
    $request = Request::create('/existing');

    $response = $this->middleware->handle($request, fn () => new Response('ok', 200));

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toBe('ok');
});

it('passes through 404 responses when no broken image path is configured', function () {
    Cms::config()->brokenImagePath = null;
    $request = Request::create('/missing');
    $request->headers->set('Accept', 'image/svg+xml');

    $response = $this->middleware->handle($request, fn () => new Response('missing', 404));

    expect($response->getStatusCode())->toBe(404)
        ->and($response->getContent())->toBe('missing');
});

it('passes through 404 responses when the request does not want an image', function () {
    $request = Request::create('/missing');
    $request->headers->set('Accept', 'text/html');

    $response = $this->middleware->handle($request, fn () => new Response('missing', 404));

    expect($response->getStatusCode())->toBe(404)
        ->and($response->getContent())->toBe('missing');
});

it('returns the configured broken image for image 404 requests', function () {
    $request = Request::create('/missing');
    $request->headers->set('Accept', 'image/svg+xml');

    $response = $this->middleware->handle($request, fn () => new Response('missing', 404));

    expect($response->getStatusCode())->toBe(404)
        ->and($response->headers->get('Content-Type'))->toContain('image/svg+xml')
        ->and($response->getContent())->toBe(File::get($this->imagePath));
});

it('throws when the configured broken image path is invalid', function () {
    Cms::config()->brokenImagePath = storage_path('framework/testing/does-not-exist.svg');

    $request = Request::create('/missing');
    $request->headers->set('Accept', 'image/svg+xml');

    $this->middleware->handle($request, fn () => new Response('missing', 404));
})->throws(RuntimeException::class, 'Invalid broken image path:');

it('rejects an alias as the configured broken image path', function () {
    Cms::config()->brokenImagePath = '@storage/broken-image.svg';

    $request = Request::create('/missing');
    $request->headers->set('Accept', 'image/svg+xml');

    $this->middleware->handle($request, fn () => new Response('missing', 404));
})->throws(RuntimeException::class, 'Path aliases require craftcms/yii2-adapter.');
