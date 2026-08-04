<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Middleware\RequireAdmin;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->middleware = app(RequireAdmin::class);
});

it('passes through when user is admin', function () {
    $admin = User::findOne();
    $request = Request::create('foo');
    $request->setUserResolver(fn () => $admin);

    $result = $this->middleware->handle($request, fn () => 'passed');

    expect($result)->toBe('passed');
});

it('throws AuthenticationException when no user', function () {
    $request = Request::create('foo');
    $request->setUserResolver(fn () => null);

    $this->middleware->handle($request, fn () => 'passed');
})->throws(AuthenticationException::class, 'Unauthenticated.');

it('aborts 403 when user is not admin', function () {
    $nonAdmin = UserModel::factory()->create(['admin' => false])->asElement();
    $request = Request::create('foo');
    $request->setUserResolver(fn () => $nonAdmin);

    $this->middleware->handle($request, fn () => 'passed');
})->throws(HttpException::class);
