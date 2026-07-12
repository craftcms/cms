<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Middleware\HandleActionRequest;
use CraftCms\Cms\Http\Middleware\HandleTokenRequest;
use CraftCms\Cms\Http\Middleware\RequireToken;
use CraftCms\Cms\RouteToken\RouteTokens;
use CraftCms\Cms\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;

beforeEach(function () {
    $this->middleware = app(HandleTokenRequest::class);
});

it('does nothing if there is no token or token header', function () {
    expect($this->middleware->handle(Request::create('foo'), fn () => 'bar'))->toBe('bar');
});

it('throws if an invalid token is passed', function () {
    $this->expectExceptionMessage('Invalid token');

    $this->middleware->handle(Request::create('foo', parameters: [
        Cms::config()->tokenParam => 'invalid token',
    ]), fn () => 'bar');
});

it('adds the token to the context', function () {
    $token = app(RouteTokens::class)->createToken('token/route');

    $this->middleware->handle(Request::create('foo', parameters: [
        Cms::config()->tokenParam => $token,
    ]), fn () => 'bar');

    expect(Context::getHidden(HandleTokenRequest::TOKEN_KEY))
        ->not()
        ->toBeNull();

    expect(Request::create('foo')->getHadToken())->toBeTrue();
});

it('does nothing more when the token does not return a route', function () {
    $result = $this->middleware->handle(Request::create('foo', parameters: [
        Cms::config()->tokenParam => Str::random(32),
    ]), fn () => 'bar');

    expect($result)->toBe('bar');
    expect(Context::getHidden(HandleTokenRequest::TOKEN_KEY))->toBeNull();
    expect(Request::create('foo')->getHadToken())->toBeFalse();
});

it('does not let an unknown token satisfy token-required routes', function () {
    $this->middleware->handle(Request::create('foo', parameters: [
        Cms::config()->tokenParam => Str::random(32),
    ]), fn () => 'bar');

    $this->expectExceptionMessage('Valid token required');

    app(RequireToken::class)->handle(Request::create('users/impersonate-with-token'), fn () => 'bar');
});

it('returns the response of the token route', function () {
    $token = app(RouteTokens::class)->createToken('token/route');

    $result = $this->middleware->handle(Request::create('foo', parameters: [
        Cms::config()->tokenParam => $token,
    ]), function (?Request $request = null) {
        if (! is_null($request)) {
            return $request->path();
        }

        return 'bar';
    });

    expect($result)->toBe('token/route');
});

it('rebinds the request after resolving a token route', function () {
    $token = app(RouteTokens::class)->createToken('token/route');

    $rebound = $this->middleware->handle(Request::create('foo', parameters: [
        Cms::config()->tokenParam => $token,
    ]), fn (Request $request) => request() === $request);

    expect($rebound)->toBeTrue();
});

it('does not let a hidden action parameter override a resolved token route', function () {
    $token = app(RouteTokens::class)->createToken('token/route');

    $path = $this->middleware->handle(Request::create('foo', 'POST', [
        Cms::config()->tokenParam => $token,
        'action' => 'users/save-user',
    ]), fn (Request $request) => app(HandleActionRequest::class)->handle(
        $request,
        fn (Request $request) => $request->path(),
    ));

    expect($path)->toBe('token/route');
});
