<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\Auth;
use CraftCms\Cms\Auth\Enums\AuthError;
use CraftCms\Cms\Auth\Events\Authenticating;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Facades\UserPermissions;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->auth = app(Auth::class);
});

test('authenticate with valid password', function () {
    $user = UserModel::factory()->createElement();

    $result = $this->auth->authenticate($user, ['password' => 'password']);

    expect($result)->toBeTrue();
    expect($this->auth->authError)->toBeNull();
});

test('authenticate with invalid password', function () {
    $user = UserModel::factory()->createElement();

    $result = $this->auth->authenticate($user, ['password' => 'wrongpassword']);

    expect($result)->toBeFalse();
    expect($this->auth->authError)->toBe(AuthError::InvalidCredentials);
});

test('authenticate with missing password', function () {
    $user = UserModel::factory()->createElement();

    $result = $this->auth->authenticate($user, ['password' => null]);

    expect($result)->toBeFalse();
    expect($this->auth->authError)->toBe(AuthError::InvalidCredentials);
});

test('authenticate with user without password', function () {
    $user = UserModel::factory()->active()->createElement([
        'password' => null,
    ]);

    $result = $this->auth->authenticate($user, ['password' => 'password']);

    expect($result)->toBeFalse();
    expect($this->auth->authError)->toBe(AuthError::InvalidCredentials);
});

test('authenticate inactive user', function () {
    $user = UserModel::factory()->createElement([
        'active' => false,
        'pending' => false,
        'suspended' => false,
    ]);

    $result = $this->auth->authenticate($user, ['password' => 'password']);

    expect($result)->toBeFalse();
    expect($this->auth->authError)->toBe(AuthError::InvalidCredentials);
});

test('authenticate pending user', function () {
    $user = UserModel::factory()->createElement([
        'pending' => true,
    ]);

    $result = $this->auth->authenticate($user, ['password' => 'password']);

    expect($result)->toBeFalse();
    expect($this->auth->authError)->toBe(AuthError::PendingVerification);
});

test('authenticate suspended user', function () {
    $user = UserModel::factory()->createElement([
        'suspended' => true,
    ]);

    $result = $this->auth->authenticate($user, ['password' => 'password']);

    expect($result)->toBeFalse();
    expect($this->auth->authError)->toBe(AuthError::AccountSuspended);
});

test('authenticate locked user with cooldown', function () {
    $user = UserModel::factory()->createElement([
        'locked' => true,
        'invalidLoginCount' => 2,
        'lockoutDate' => now(),
    ]);

    Cms::config()->cooldownDuration = 60;

    $result = $this->auth->authenticate($user, ['password' => 'password']);

    expect($result)->toBeFalse();
    expect($this->auth->authError)->toBe(AuthError::AccountCooldown);
});

test('authenticate locked user without cooldown', function () {
    $user = UserModel::factory()->createElement([
        'locked' => true,
        'invalidLoginCount' => 2,
        'lockoutDate' => now(),
    ]);

    Cms::config()->cooldownDuration = null;

    $result = $this->auth->authenticate($user, ['password' => 'password']);

    expect($result)->toBeFalse();
    expect($this->auth->authError)->toBe(AuthError::AccountLocked);
});

test('authenticate user requiring password reset', function () {
    $user = UserModel::factory()->createElement([
        'passwordResetRequired' => true,
    ]);

    $result = $this->auth->authenticate($user, ['password' => 'password']);

    expect($result)->toBeFalse();
    expect($this->auth->authError)->toBe(AuthError::PasswordResetRequired);
});

test('authenticate without CP access', function () {
    Edition::set(Edition::Pro);

    $user = UserModel::factory()->createElement([
        'admin' => false,
    ]);

    Cms::config()->cpTrigger = '/';

    $result = $this->auth->authenticate($user, ['password' => 'password']);

    expect($result)->toBeFalse();
    expect($this->auth->authError)->toBe(AuthError::NoCpAccess);
});

test('authenticate with CP offline no access', function () {
    Edition::set(Edition::Pro);

    $user = UserModel::factory()->createElement([
        'admin' => false,
    ]);
    UserPermissions::saveUserPermissions($user->id, [
        'accessCp',
    ]);

    Cms::config()->cpTrigger = '/';
    Cms::config()->isSystemLive = false;

    $result = $this->auth->authenticate($user, ['password' => 'password']);

    expect($result)->toBeFalse();
    expect($this->auth->authError)->toBe(AuthError::NoCpOfflineAccess);
});

test('authenticate with site offline no access', function () {
    Edition::set(Edition::Pro);

    $user = UserModel::factory()->createElement([
        'admin' => false,
    ]);

    Cms::config()->isSystemLive = false;

    $result = $this->auth->authenticate($user, ['password' => 'password']);

    expect($result)->toBeFalse();
    expect($this->auth->authError)->toBe(AuthError::NoSiteOfflineAccess);
});

test('authenticating event can block auth', function () {
    $user = UserModel::factory()->createElement();

    Event::listen(Authenticating::class, function (Authenticating $event) {
        $event->authError = AuthError::InvalidCredentials;
    });

    $result = $this->auth->authenticate($user, ['password' => 'password']);

    expect($result)->toBeFalse();
    expect($this->auth->authError)->toBe(AuthError::InvalidCredentials);
});

test('authenticating event can skip password check', function () {
    $user = UserModel::factory()->createElement();

    Event::listen(Authenticating::class, function (Authenticating $event) {
        $event->performAuthentication = false;
    });

    $result = $this->auth->authenticate($user, ['password' => 'wrongpassword']);

    expect($result)->toBeTrue();
});
