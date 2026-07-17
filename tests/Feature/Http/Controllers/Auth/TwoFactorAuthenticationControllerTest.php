<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\Models\Authenticator;
use CraftCms\Cms\Auth\Models\RecoveryCodes;
use CraftCms\Cms\Auth\TwoFactorRateLimiter;
use CraftCms\Cms\Http\Controllers\Auth\TwoFactorAuthenticationController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Route;
use PragmaRX\Google2FA\Google2FA;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withServerVariables;
use function Pest\Laravel\withSession;

test('showForm redirects to login when user id in session is invalid', function () {
    withSession(['user.id' => 99999]);

    get(action([TwoFactorAuthenticationController::class, 'showForm']))
        ->assertRedirect();
});

test('showForm aborts when user has no active 2fa methods', function () {
    $user = User::findOne();
    withSession(['user.id' => $user->id, 'user.pending_2fa_at' => now()->timestamp]);

    get(action([TwoFactorAuthenticationController::class, 'showForm']))
        ->assertStatus(400);
});

test('showForm aborts with invalid method class', function () {
    $user = User::findOne();
    withSession(['user.id' => $user->id, 'user.pending_2fa_at' => now()->timestamp]);

    get(action([TwoFactorAuthenticationController::class, 'showForm'], [
        'method' => 'InvalidMethodClass',
    ]))->assertStatus(400);
});

test('verify fails with invalid totp code', function () {
    $user = User::findOne();
    withSession(['user.id' => $user->id, 'user.pending_2fa_at' => now()->timestamp]);

    postJson(action([TwoFactorAuthenticationController::class, 'verify']), [
        'code' => '000000',
    ])->assertStatus(400);
});

test('verifyRecoveryCode fails with invalid recovery code', function () {
    $user = User::findOne();
    withSession(['user.id' => $user->id, 'user.pending_2fa_at' => now()->timestamp]);

    postJson(action([TwoFactorAuthenticationController::class, 'verifyRecoveryCode']), [
        'code' => 'invalid-recovery-code',
    ])->assertStatus(400);
});

test('verify returns success with valid TOTP code', function () {
    $user = User::findOne();
    $secret = (new Google2FA)->generateSecretKey();

    Authenticator::create([
        'userId' => $user->id,
        'auth2faSecret' => $secret,
    ]);

    withSession(['user.id' => $user->id, 'user.pending_2fa_at' => now()->timestamp]);

    postJson(action([TwoFactorAuthenticationController::class, 'verify']), [
        'code' => (new Google2FA)->getCurrentOtp($secret),
    ])->assertOk();
});

test('verifyRecoveryCode returns success with valid recovery code', function () {
    $user = User::findOne();

    RecoveryCodes::create([
        'userId' => $user->id,
        'recoveryCodes' => ['abc123-def456'],
    ]);

    withSession(['user.id' => $user->id, 'user.pending_2fa_at' => now()->timestamp]);

    postJson(action([TwoFactorAuthenticationController::class, 'verifyRecoveryCode']), [
        'code' => 'abc123-def456',
    ])->assertOk();

    expect(RecoveryCodes::where('userId', $user->id)->first()->recoveryCodes)->toBe([false]);
});

test('verify requires code parameter', function () {
    $user = User::findOne();
    withSession(['user.id' => $user->id, 'user.pending_2fa_at' => now()->timestamp]);

    postJson(action([TwoFactorAuthenticationController::class, 'verify']), [])
        ->assertJsonValidationErrorFor('code');
});

test('verifyRecoveryCode requires code parameter', function () {
    $user = User::findOne();
    withSession(['user.id' => $user->id, 'user.pending_2fa_at' => now()->timestamp]);

    postJson(action([TwoFactorAuthenticationController::class, 'verifyRecoveryCode']), [])
        ->assertJsonValidationErrorFor('code');
});

test('verification routes share a two-factor rate limit', function () {
    $totpRoute = Route::getRoutes()->getByAction(TwoFactorAuthenticationController::class.'@verify');
    $recoveryCodeRoute = Route::getRoutes()->getByAction(TwoFactorAuthenticationController::class.'@verifyRecoveryCode');

    expect($totpRoute->middleware())->toContain('throttle:'.TwoFactorRateLimiter::NAME)
        ->and($recoveryCodeRoute->middleware())->toContain('throttle:'.TwoFactorRateLimiter::NAME);
});

test('TOTP and recovery code verification share an attempt budget', function () {
    $user = User::findOne();
    withSession(['user.id' => $user->id, 'user.pending_2fa_at' => now()->timestamp]);
    withServerVariables(['REMOTE_ADDR' => '192.0.2.42']);

    foreach (range(1, 3) as $_) {
        postJson(action([TwoFactorAuthenticationController::class, 'verify']), [
            'code' => '000000',
        ])->assertStatus(400);
    }

    foreach (range(1, 2) as $_) {
        postJson(action([TwoFactorAuthenticationController::class, 'verifyRecoveryCode']), [
            'code' => 'invalid-recovery-code',
        ])->assertStatus(400);
    }

    postJson(action([TwoFactorAuthenticationController::class, 'verify']), [
        'code' => '000000',
    ])->assertTooManyRequests();
});

test('showForm handles JSON requests', function () {
    $user = User::findOne();
    Authenticator::create([
        'userId' => $user->id,
        'auth2faSecret' => 'secret',
    ]);

    withSession(['user.id' => $user->id, 'user.pending_2fa_at' => now()->timestamp]);

    getJson(action([TwoFactorAuthenticationController::class, 'showForm']))
        ->assertJsonStructure(['authMethod', 'otherMethods', 'authForm', 'returnUrl']);
});
