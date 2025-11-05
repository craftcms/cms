<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Middleware\HandleTokenRequest;
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
    $this->middleware->handle(Request::create('foo', parameters: [
        Cms::config()->tokenParam => Str::random(32),
    ]), fn () => 'bar');

    expect(Context::getHidden(HandleTokenRequest::TOKEN_KEY))
        ->not()
        ->toBeNull();
});

it('does nothing more when the token does not return a route', function () {
    $result = $this->middleware->handle(Request::create('foo', parameters: [
        Cms::config()->tokenParam => Str::random(32),
    ]), fn () => 'bar');

    expect($result)->toBe('bar');
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

    /** @var ?\Illuminate\Support\Uri $originalUri */
    $originalUri = Context::getHidden(HandleTokenRequest::ORIGINAL_URI_KEY);

    expect($originalUri)->not()->toBeNull();
    expect($originalUri->value())->toBe(url('foo'));
});
