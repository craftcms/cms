<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Http\Middleware\EnsureTwoFactorChallengeIsRecent;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/test-2fa-recent', fn () => 'ok')->middleware(EnsureTwoFactorChallengeIsRecent::class);
    Route::post('/test-2fa-recent', fn () => 'ok')->middleware(EnsureTwoFactorChallengeIsRecent::class);
});

test('allows request through when pending session is fresh', function () {
    $this->withSession(['user.pending_2fa_at' => now()->timestamp])
        ->get('/test-2fa-recent')
        ->assertOk()
        ->assertSee('ok');
});

test('redirects to login when pending_2fa_at is missing', function () {
    $this->get('/test-2fa-recent')
        ->assertRedirect(CpAuthPath::Login->value);
});

test('redirects to login when pending_2fa_at is older than 5 minutes', function () {
    $this->withSession(['user.pending_2fa_at' => now()->subSeconds(301)->timestamp])
        ->get('/test-2fa-recent')
        ->assertRedirect(CpAuthPath::Login->value);
});

test('allows request through when pending session is exactly at the boundary', function () {
    $this->withSession(['user.pending_2fa_at' => now()->subSeconds(300)->timestamp])
        ->get('/test-2fa-recent')
        ->assertOk();
});

test('clears user session keys when challenge has expired', function () {
    $this->withSession([
        'user.id' => 1,
        'user.pending_2fa_at' => now()->subSeconds(301)->timestamp,
    ])->get('/test-2fa-recent');

    expect(session()->has('user.id'))->toBeFalse()
        ->and(session()->has('user.pending_2fa_at'))->toBeFalse();
});

test('returns 401 json when session is expired and request wants json', function () {
    $this->withSession(['user.pending_2fa_at' => now()->subSeconds(301)->timestamp])
        ->postJson('/test-2fa-recent')
        ->assertStatus(401)
        ->assertJsonPath('message', fn (string $m) => str_contains($m, 'expired'));
});

test('returns 401 json when pending_2fa_at is missing and request wants json', function () {
    $this->postJson('/test-2fa-recent')
        ->assertStatus(401);
});
