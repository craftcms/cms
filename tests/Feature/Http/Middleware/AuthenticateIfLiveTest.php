<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Middleware\AuthenticateIfLive;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->middleware = app(AuthenticateIfLive::class);
});

it('passes through during maintenance mode', function () {
    app()->maintenanceMode()->activate([]);

    $request = Request::create('foo');
    $request->setUserResolver(fn () => null);

    $result = $this->middleware->handle($request, fn () => 'passed');

    expect($result)->toBe('passed');
});

it('authenticates outside maintenance mode when a user exists', function () {
    $user = User::findOne();
    actingAs($user);

    $request = Request::create('foo');
    $request->setUserResolver(fn () => $user);

    $result = $this->middleware->handle($request, fn () => 'passed');

    expect($result)->toBe('passed');
});

it('throws AuthenticationException outside maintenance mode when no user exists', function () {
    $request = Request::create('foo');
    $request->setUserResolver(fn () => null);

    $this->middleware->handle($request, fn () => 'passed');
})->throws(AuthenticationException::class);
