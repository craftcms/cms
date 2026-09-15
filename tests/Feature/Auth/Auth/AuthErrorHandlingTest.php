<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\AuthMethods;
use CraftCms\Cms\Auth\Enums\AuthError;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Session;

beforeEach(function () {
    $this->auth = app(AuthMethods::class);
    Session::flush();
});

test('getAuthError for inactive user', function () {
    $user = UserModel::factory()->createElement([
        'active' => false,
        'pending' => false,
        'suspended' => false,
    ]);

    $result = $this->auth->getAuthError($user);

    expect($result)->toBe(AuthError::InvalidCredentials);
});

test('getAuthError for pending user', function () {
    $user = UserModel::factory()->pending()->createElement();

    $result = $this->auth->getAuthError($user);

    expect($result)->toBe(AuthError::PendingVerification);
});

test('getAuthError for suspended user', function () {
    $user = UserModel::factory()->suspended()->createElement();

    $result = $this->auth->getAuthError($user);

    expect($result)->toBe(AuthError::AccountSuspended);
});

test('getAuthError for locked user with cooldown', function () {
    $user = UserModel::factory()->locked()->createElement();

    Cms::config()->cooldownDuration = 60;

    $result = $this->auth->getAuthError($user);

    expect($result)->toBe(AuthError::AccountCooldown);
});

test('getAuthError for locked user no cooldown', function () {
    $user = UserModel::factory()->locked()->createElement();

    Cms::config()->cooldownDuration = null;

    $result = $this->auth->getAuthError($user);

    expect($result)->toBe(AuthError::AccountLocked);
});

test('getAuthError for password reset required', function () {
    $user = UserModel::factory()->createElement(['passwordResetRequired' => true]);

    $result = $this->auth->getAuthError($user);

    expect($result)->toBe(AuthError::PasswordResetRequired);
});

test('getAuthError uses explicit CP context or defaults to the request', function (bool $cpRequest, ?bool $cpContext, ?AuthError $expected) {
    Edition::set(Edition::Pro);
    Cms::config()->cpTrigger = $cpRequest ? '/' : 'admin';
    $user = UserModel::factory()->createElement(['admin' => false]);

    expect($this->auth->getAuthError($user, $cpContext))->toBe($expected);
})->with([
    'CP default' => [true, null, AuthError::NoCpAccess],
    'site default' => [false, null, null],
    'explicit CP on site' => [false, true, AuthError::NoCpAccess],
    'explicit site on CP' => [true, false, null],
]);

test('getAuthError returns null for valid user', function () {
    $user = UserModel::factory()->createElement(['admin' => true]);

    $result = $this->auth->getAuthError($user);

    expect($result)->toBeNull();
});

test('getLoginFailureInfo returns correct messages', function () {
    $user = UserModel::factory()->createElement();

    $result = $this->auth->getLoginFailureInfo(AuthError::PendingVerification, $user);

    expect($result[0])->toBe(AuthError::PendingVerification);
    expect($result[1])->not()->toBeEmpty();
});

test('getLoginFailureInfo with preventUserEnumeration', function () {
    Cms::config()->preventUserEnumeration = true;

    $user = UserModel::factory()->createElement();

    $result = $this->auth->getLoginFailureInfo(AuthError::AccountLocked, $user);

    expect($result[0])->toBe(AuthError::InvalidCredentials);
});

test('handleInvalidLogin increments invalid count', function () {
    $user = UserModel::factory()->createElement();

    $this->auth->handleInvalidLogin($user);

    expect($user->invalidLoginCount)->toBeGreaterThan(0);
});

test('handleInvalidLogin locks after max attempts', function () {
    Cms::config()->maxInvalidLogins = 3;

    $user = UserModel::factory()->createElement();

    for ($i = 0; $i < 3; $i++) {
        $this->auth->handleInvalidLogin($user);
    }

    expect($user->locked)->toBeTrue();
});

test('getAuthMethodErrorMessage defaults', function () {
    $message = $this->auth->getAuthMethodErrorMessage();

    expect($message)->not()->toBeEmpty();
});

test('getAuthMethodErrorMessage returns error msg', function () {
    $user = UserModel::factory()->createElement();
    $this->auth->setUser($user);

    $message = $this->auth->getAuthMethodErrorMessage();

    expect($message)->not()->toBeEmpty();
});
