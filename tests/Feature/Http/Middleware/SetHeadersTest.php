<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Middleware\HandleTokenRequest;
use CraftCms\Cms\Http\Middleware\SetHeaders;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

beforeEach(function () {
    $this->generalConfig = Cms::config();
    $this->middleware = app(SetHeaders::class);
});

it('passes through non-Response values', function () {
    $request = Request::create('foo');

    $result = $this->middleware->handle($request, fn () => 'bar');

    expect($result)->toBe('bar');
});

it('sets no-cache headers for CP requests', function () {
    $request = Request::create('/'.Cms::config()->cpTrigger);
    $request->setMethod('GET');

    $response = $this->middleware->handle($request, fn () => new Response);

    expect($response->headers->has('Cache-Control'))->toBeTrue();
});

it('sets X-Robots-Tag header for CP requests', function () {
    $request = Request::create('/'.Cms::config()->cpTrigger);
    $request->setMethod('GET');

    $response = $this->middleware->handle($request, fn () => new Response);

    expect($response->headers->get('X-Robots-Tag'))->toBe('none');
});

it('sets Content-Security-Policy header for CP requests', function () {
    $request = Request::create('/'.Cms::config()->cpTrigger);
    $request->setMethod('GET');

    $response = $this->middleware->handle($request, fn () => new Response);

    expect($response->headers->get('Content-Security-Policy'))->toBe("frame-ancestors 'self'");
});

it('sets X-Frame-Options header for CP requests', function () {
    $request = Request::create('/'.Cms::config()->cpTrigger);
    $request->setMethod('GET');

    $response = $this->middleware->handle($request, fn () => new Response);

    expect($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN');
});

it('sets X-Content-Type-Options header for CP requests', function () {
    $request = Request::create('/'.Cms::config()->cpTrigger);
    $request->setMethod('GET');

    $response = $this->middleware->handle($request, fn () => new Response);

    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
});

it('sets no-cache headers for action requests', function () {
    $request = Request::create('/'.Cms::config()->actionTrigger.'/test');

    $response = $this->middleware->handle($request, fn () => new Response);

    expect($response->headers->has('Cache-Control'))->toBeTrue();
});

it('sets X-Robots-Tag header when disallowRobots is true', function () {
    $this->generalConfig->disallowRobots();

    $request = Request::create('foo');

    $response = $this->middleware->handle($request, fn () => new Response);

    expect($response->headers->get('X-Robots-Tag'))->toBe('none');
});

it('sets X-Robots-Tag header when token param is present', function () {
    $request = Request::create('foo', parameters: [
        HandleTokenRequest::TOKEN_KEY => 'test-token',
    ]);

    $response = $this->middleware->handle($request, fn () => new Response);

    expect($response->headers->get('X-Robots-Tag'))->toBe('none');
});

it('sets X-Robots-Tag header when token header is present', function () {
    $request = Request::create('foo');
    $request->headers->set(HandleTokenRequest::TOKEN_HEADER, 'test-token');

    $response = $this->middleware->handle($request, fn () => new Response);

    expect($response->headers->get('X-Robots-Tag'))->toBe('none');
});

it('does not set X-Robots-Tag header for normal site requests', function () {
    $this->generalConfig->disallowRobots(false);

    $request = Request::create('foo');

    $response = $this->middleware->handle($request, fn () => new Response);

    expect($response->headers->has('X-Robots-Tag'))->toBeFalse();
});

it('does not set security headers for site requests', function () {
    $this->generalConfig->disallowRobots(false);

    $request = Request::create('foo');

    $response = $this->middleware->handle($request, fn () => new Response);

    expect($response->headers->has('Content-Security-Policy'))->toBeFalse();
    expect($response->headers->has('X-Frame-Options'))->toBeFalse();
    expect($response->headers->has('X-Content-Type-Options'))->toBeFalse();
});
