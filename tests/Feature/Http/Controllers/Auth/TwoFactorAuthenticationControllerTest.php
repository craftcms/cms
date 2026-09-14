<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Auth\Models\Authenticator;
use CraftCms\Cms\Auth\Models\RecoveryCodes;
use CraftCms\Cms\Auth\TwoFactorRateLimiter;
use CraftCms\Cms\Http\Controllers\Auth\LoginController;
use CraftCms\Cms\Http\Controllers\Auth\TwoFactorAuthenticationController;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\Users;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use PragmaRX\Google2FA\Google2FA;

use function Pest\Laravel\actingAs;
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

    get(action([TwoFactorAuthenticationController::class, 'showForm']))->assertBadRequest();
});

test('showForm aborts with invalid method class', function () {
    $user = User::findOne();
    withSession(['user.id' => $user->id, 'user.pending_2fa_at' => now()->timestamp]);

    get(action([TwoFactorAuthenticationController::class, 'showForm'], [
        'method' => 'InvalidMethodClass',
    ]))->assertBadRequest();
});

test('verify fails with invalid totp code', function () {
    $user = User::findOne();
    withSession(['user.id' => $user->id, 'user.pending_2fa_at' => now()->timestamp]);

    postJson(action([TwoFactorAuthenticationController::class, 'verify']), [
        'code' => '000000',
    ])->assertBadRequest();
});

test('verifyRecoveryCode fails with invalid recovery code', function () {
    $user = User::findOne();
    withSession(['user.id' => $user->id, 'user.pending_2fa_at' => now()->timestamp]);

    postJson(action([TwoFactorAuthenticationController::class, 'verifyRecoveryCode']), [
        'code' => 'invalid-recovery-code',
    ])->assertBadRequest();
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

test('verify rejects a user suspended after the first factor', function () {
    $user = User::findOne();
    $secret = (new Google2FA)->generateSecretKey();

    Authenticator::create([
        'userId' => $user->id,
        'auth2faSecret' => $secret,
    ]);

    withSession([
        'user.id' => $user->id,
        'user.pending_2fa_at' => now()->timestamp,
    ]);

    app(Users::class)->suspendUser($user);

    postJson(action([TwoFactorAuthenticationController::class, 'verify']), [
        'code' => (new Google2FA)->getCurrentOtp($secret),
    ])->assertBadRequest()
        ->assertJsonPath('message', 'Account suspended.');

    expect(Auth::check())->toBeFalse()
        ->and(session()->has('user.id'))->toBeFalse()
        ->and(session()->has('user.pending_2fa_at'))->toBeFalse();
});

test('verify preserves the remember-me choice', function (bool $remember) {
    $user = User::findOne();
    $secret = (new Google2FA)->generateSecretKey();

    Authenticator::create([
        'userId' => $user->id,
        'auth2faSecret' => $secret,
    ]);

    postJson(action([LoginController::class, 'attemptLogin']), [
        'loginName' => $user->email,
        'password' => 'craftcms2018!!',
        'rememberMe' => $remember,
    ])->assertRedirect();

    $response = postJson(action([TwoFactorAuthenticationController::class, 'verify']), [
        'code' => (new Google2FA)->getCurrentOtp($secret),
    ])->assertOk();

    $remember
        ? $response->assertCookie(Auth::guard()->getRecallerName())
        : $response->assertCookieMissing(Auth::guard()->getRecallerName());
})->with([
    'remembered' => true,
    'not remembered' => false,
]);

test('impersonation verifies the impersonator and retains the impersonated user', function () {
    $impersonator = User::findOne();
    $impersonatedUser = UserModel::factory()->createElement();
    $impersonatorSecret = (new Google2FA)->generateSecretKey();
    $impersonatedSecret = (new Google2FA)->generateSecretKey();

    Authenticator::create([
        'userId' => $impersonator->id,
        'auth2faSecret' => $impersonatorSecret,
    ]);
    Authenticator::create([
        'userId' => $impersonatedUser->id,
        'auth2faSecret' => $impersonatedSecret,
    ]);

    actingAs($impersonatedUser);
    app(Impersonation::class)->setImpersonatorId($impersonator->id);

    postJson(action([LoginController::class, 'attemptLogin']), [
        'loginName' => $impersonator->email,
        'password' => 'craftcms2018!!',
        'forElevatedSession' => true,
        'rememberMe' => true,
    ])->assertRedirect();

    postJson(action([TwoFactorAuthenticationController::class, 'verify']), [
        'code' => (new Google2FA)->getCurrentOtp($impersonatedSecret),
    ])->assertBadRequest();

    $response = postJson(action([TwoFactorAuthenticationController::class, 'verify']), [
        'code' => (new Google2FA)->getCurrentOtp($impersonatorSecret),
    ])->assertOk();

    expect(Auth::id())->toBe($impersonatedUser->id);
    $response->assertCookieMissing(Auth::guard()->getRecallerName());
});

test('impersonation requires the impersonator second factor when the impersonated user has none', function () {
    $impersonator = User::findOne();
    $impersonatedUser = UserModel::factory()->createElement();
    $secret = (new Google2FA)->generateSecretKey();

    Authenticator::create([
        'userId' => $impersonator->id,
        'auth2faSecret' => $secret,
    ]);

    actingAs($impersonatedUser);
    app(Impersonation::class)->setImpersonatorId($impersonator->id);

    postJson(action([LoginController::class, 'attemptLogin']), [
        'loginName' => $impersonator->email,
        'password' => 'craftcms2018!!',
        'forElevatedSession' => true,
    ])->assertRedirect();

    expect(session('user.id'))->toBe($impersonator->id)
        ->and(session('user.login_id'))->toBe($impersonatedUser->id);

    postJson(action([TwoFactorAuthenticationController::class, 'verify']), [
        'code' => (new Google2FA)->getCurrentOtp($secret),
    ])->assertOk();

    expect(Auth::id())->toBe($impersonatedUser->id);
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
        ])->assertBadRequest();
    }

    foreach (range(1, 2) as $_) {
        postJson(action([TwoFactorAuthenticationController::class, 'verifyRecoveryCode']), [
            'code' => 'invalid-recovery-code',
        ])->assertBadRequest();
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

test('showForm strips a javascript: returnUrl', function (string $returnUrl) {
    $user = User::findOne();
    Authenticator::create([
        'userId' => $user->id,
        'auth2faSecret' => 'secret',
    ]);

    withSession(['user.id' => $user->id, 'user.pending_2fa_at' => now()->timestamp]);

    $response = getJson(action([TwoFactorAuthenticationController::class, 'showForm'], [
        'returnUrl' => $returnUrl,
    ]))->assertOk();

    expect($response->json('returnUrl'))
        ->not->toContain('javascript:');
})->with([
    'javascript:alert(1)',
    "java\tscript:alert(1)",
    'JAVASCRIPT:alert(1)',
]);
