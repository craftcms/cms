<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\Auth;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Session;

beforeEach(function () {
    $this->auth = app(Auth::class);
    Session::flush();
});

test('getUser returns session user', function () {
    $user = User::factory()->createElement();
    Session::put('user.id', $user->id);

    $result = $this->auth->getUser();

    expect($result)->not()->toBeNull();
    expect($result->id)->toBe($user->id);
});

test('getUser returns null when no session', function () {
    $result = $this->auth->getUser();

    expect($result)->toBeNull();
});

test('getUser returns cached user', function () {
    $user = User::factory()->createElement();
    Session::put('user.id', $user->id);

    $user1 = $this->auth->getUser();
    $user2 = $this->auth->getUser();

    expect($user1)->toBe($user2);
});

test('setUser stores user in session', function () {
    $user = User::factory()->createElement();

    $this->auth->setUser($user);

    expect(Session::get('user.id'))->toBe($user->id);
});

test('setUser with null clears session', function () {
    $user = User::factory()->createElement();
    Session::put('user.id', $user->id);

    $this->auth->setUser(null);

    expect(Session::get('user.id'))->toBeNull();
});

test('setUser handles user not found', function () {
    Session::put('user.id', 99999);

    $result = $this->auth->getUser();

    expect($result)->toBeNull();
});
