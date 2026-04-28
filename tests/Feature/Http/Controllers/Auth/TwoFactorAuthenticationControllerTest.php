<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\Models\Authenticator;
use CraftCms\Cms\Http\Controllers\Auth\TwoFactorAuthenticationController;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withSession;

test('showForm redirects to login when user id in session is invalid', function () {
    withSession(['user.id' => 99999]);

    get(action([TwoFactorAuthenticationController::class, 'showForm']))
        ->assertRedirect();
});

test('showForm aborts when user has no active 2fa methods', function () {
    $user = User::findOne();
    withSession(['user.id' => $user->id]);

    get(action([TwoFactorAuthenticationController::class, 'showForm']))
        ->assertStatus(400);
});

test('showForm aborts with invalid method class', function () {
    $user = User::findOne();
    withSession(['user.id' => $user->id]);

    get(action([TwoFactorAuthenticationController::class, 'showForm'], [
        'method' => 'InvalidMethodClass',
    ]))->assertStatus(400);
});

test('verify fails with invalid totp code', function () {
    $user = User::findOne();
    withSession(['user.id' => $user->id]);

    postJson(action([TwoFactorAuthenticationController::class, 'verify']), [
        'code' => '000000',
    ])->assertStatus(400);
});

test('verifyRecoveryCode fails with invalid recovery code', function () {
    $user = User::findOne();
    withSession(['user.id' => $user->id]);

    postJson(action([TwoFactorAuthenticationController::class, 'verifyRecoveryCode']), [
        'code' => 'invalid-recovery-code',
    ])->assertStatus(400);
});

test('verify returns success with valid TOTP code', function () {
    $user = User::findOne();
    withSession(['user.id' => $user->id]);

    $this->withoutExceptionHandling();

    postJson(action([TwoFactorAuthenticationController::class, 'verify']), [
        'code' => '123456',
    ]);
})->todo('Implement once Auth service is ported and can be faked.');

test('verifyRecoveryCode returns success with valid recovery code', function () {
    $user = User::findOne();
    withSession(['user.id' => $user->id]);

    postJson(action([TwoFactorAuthenticationController::class, 'verifyRecoveryCode']), [
        'code' => 'some-recovery-code',
    ]);
})->todo('Implement once Auth service is ported and can be faked.');

test('verify requires code parameter', function () {
    $user = User::findOne();
    withSession(['user.id' => $user->id]);

    postJson(action([TwoFactorAuthenticationController::class, 'verify']), [])
        ->assertJsonValidationErrorFor('code');
});

test('verifyRecoveryCode requires code parameter', function () {
    $user = User::findOne();
    withSession(['user.id' => $user->id]);

    postJson(action([TwoFactorAuthenticationController::class, 'verifyRecoveryCode']), [])
        ->assertJsonValidationErrorFor('code');
});

test('showForm handles JSON requests', function () {
    $user = User::findOne();
    Authenticator::create([
        'userId' => $user->id,
        'auth2faSecret' => 'secret',
    ]);

    withSession(['user.id' => $user->id]);

    getJson(action([TwoFactorAuthenticationController::class, 'showForm']))
        ->assertJsonStructure(['authMethod', 'otherMethods', 'authForm', 'returnUrl']);
});
