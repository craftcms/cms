<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\AuthMethods;
use CraftCms\Cms\Auth\Methods\TOTP;
use CraftCms\Cms\Auth\Models\Authenticator;
use CraftCms\Cms\User\Models\User;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function () {
    $this->auth = app(AuthMethods::class);
});

afterEach(function () {
    Session::flush();
});

test('verifyMethod returns false without verifying when the per-user lock cannot be acquired', function () {
    $userModel = User::factory()->create();
    $secret = (new Google2FA)->generateSecretKey();

    Authenticator::create([
        'userId' => $userModel->id,
        'auth2faSecret' => $secret,
    ]);

    Session::put('user.id', $userModel->id);

    Cache::shouldReceive('lock')
        ->with("auth-verify:{$userModel->id}", 10)
        ->andReturn(
            Mockery::mock(Lock::class)
                ->shouldReceive('block')
                ->andThrow(LockTimeoutException::class)
                ->getMock()
        );

    $code = (new Google2FA)->getCurrentOtp($secret);

    expect($this->auth->verifyMethod(TOTP::class, $code))->toBeFalse();
});

test('verifyMethod still verifies successfully when the lock is acquired', function () {
    $userModel = User::factory()->admin()->create();
    $secret = (new Google2FA)->generateSecretKey();

    Authenticator::create([
        'userId' => $userModel->id,
        'auth2faSecret' => $secret,
    ]);

    Session::put('user.id', $userModel->id);
    Session::put('user.login_id', $userModel->id);

    $code = (new Google2FA)->getCurrentOtp($secret);

    expect($this->auth->verifyMethod(TOTP::class, $code))->toBeTrue();
});
