<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Middleware\AuthenticateIfLive;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->middleware = app(AuthenticateIfLive::class);
});

it('passes through when app is not live', function () {
    Cms::config()->isSystemLive = false;

    $request = Request::create('foo');
    $request->setUserResolver(fn () => null);

    $result = $this->middleware->handle($request, fn () => 'passed');

    expect($result)->toBe('passed');
});

it('authenticates when app is live and user exists', function () {
    Cms::config()->isSystemLive = true;

    $user = User::findOne();
    actingAs($user);

    $request = Request::create('foo');
    $request->setUserResolver(fn () => $user);

    $result = $this->middleware->handle($request, fn () => 'passed');

    expect($result)->toBe('passed');
});

it('throws AuthenticationException when app is live and no user', function () {
    Cms::config()->isSystemLive = true;

    $request = Request::create('foo');
    $request->setUserResolver(fn () => null);

    $this->middleware->handle($request, fn () => 'passed');
})->throws(AuthenticationException::class);
