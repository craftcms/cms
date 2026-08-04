<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Middleware\EnsureTwoFactorChallengeIsRecent;
use CraftCms\Cms\Support\Url;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Site route (no admin prefix)
    Route::get('/test-2fa-recent', fn () => 'ok')->middleware(EnsureTwoFactorChallengeIsRecent::class);
    Route::post('/test-2fa-recent', fn () => 'ok')->middleware(EnsureTwoFactorChallengeIsRecent::class);

    // CP route (with admin prefix)
    Route::get('/admin/test-2fa-recent', fn () => 'ok')->middleware(EnsureTwoFactorChallengeIsRecent::class);
    Route::post('/admin/test-2fa-recent', fn () => 'ok')->middleware(EnsureTwoFactorChallengeIsRecent::class);
});

afterEach(function () {
    Date::setTestNow();
});

test('allows request through when pending session is fresh', function () {
    $this->withSession(['user.pending_2fa_at' => now()->timestamp])
        ->get('/admin/test-2fa-recent')
        ->assertOk()
        ->assertSee('ok');
});

test('CP request redirects to CP login when pending_2fa_at is missing', function () {
    $this->get('/admin/test-2fa-recent')
        ->assertRedirect(Url::cpUrl(CpAuthPath::Login->value));
});

test('CP request redirects to CP login when pending_2fa_at is older than 5 minutes', function () {
    $this->withSession(['user.pending_2fa_at' => now()->subSeconds(301)->timestamp])
        ->get('/admin/test-2fa-recent')
        ->assertRedirect(Url::cpUrl(CpAuthPath::Login->value));
});

test('allows request through when pending session is exactly at the boundary', function () {
    Date::setTestNow(now());

    $this->withSession(['user.pending_2fa_at' => now()->subSeconds(300)->timestamp])
        ->get('/admin/test-2fa-recent')
        ->assertOk();
});

test('clears user session keys when challenge has expired', function () {
    $this->withSession([
        'user.id' => 1,
        'user.login_id' => 2,
        'user.remember' => true,
        'user.pending_2fa_at' => now()->subSeconds(301)->timestamp,
    ])->get('/admin/test-2fa-recent');

    expect(session()->has('user.id'))->toBeFalse()
        ->and(session()->has('user.login_id'))->toBeFalse()
        ->and(session()->has('user.remember'))->toBeFalse()
        ->and(session()->has('user.pending_2fa_at'))->toBeFalse();
});

test('returns 419 json when session is expired and request wants json', function () {
    $this->withSession(['user.pending_2fa_at' => now()->subSeconds(301)->timestamp])
        ->postJson('/admin/test-2fa-recent')
        ->assertStatus(419)
        ->assertJsonPath('message', fn (string $m) => str_contains($m, 'expired'));
});

test('returns 419 json when pending_2fa_at is missing and request wants json', function () {
    $this->postJson('/admin/test-2fa-recent')
        ->assertStatus(419);
});

test('site request redirects to configured loginPath when session expired', function () {
    Cms::config()->loginPath = 'member/login';

    $this->withSession(['user.pending_2fa_at' => now()->subSeconds(301)->timestamp])
        ->get('/test-2fa-recent')
        ->assertRedirect('member/login');
});

test('site request falls back to CP login when loginPath is not configured', function () {
    Cms::config()->loginPath = null;

    $this->withSession(['user.pending_2fa_at' => now()->subSeconds(301)->timestamp])
        ->get('/test-2fa-recent')
        ->assertRedirect(Url::cpUrl(CpAuthPath::Login->value));
});

test('CP request always redirects to CP login regardless of loginPath config', function () {
    Cms::config()->loginPath = 'member/login';

    $this->withSession(['user.pending_2fa_at' => now()->subSeconds(301)->timestamp])
        ->get('/admin/test-2fa-recent')
        ->assertRedirect(Url::cpUrl(CpAuthPath::Login->value));
});
