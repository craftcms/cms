<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\Auth;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Http\Middleware\Enforce2fa;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/test-2fa', fn () => 'ok')->middleware(Enforce2fa::class);
});

test('allows guest through', function () {
    $this->get('/test-2fa')
        ->assertStatus(200)
        ->assertSee('ok');
});

test('allows user through when 2fa is disabled', function () {
    $user = UserModel::factory()->createElement();
    app(GeneralConfig::class)->disable2fa = true;

    $this->actingAs($user, 'craft')
        ->get('/test-2fa')
        ->assertStatus(200)
        ->assertSee('ok');
});

test('allows user through when 2fa is not required', function () {
    $user = UserModel::factory()->createElement();
    // By default 2FA is not required for a standard user in Solo edition or if not configured

    $this->actingAs($user, 'craft')
        ->get('/test-2fa')
        ->assertStatus(200)
        ->assertSee('ok');
});

test('redirects user to 2fa setup when required but not active', function () {
    $user = UserModel::factory()->createElement();

    // Force 2FA requirement for all users
    ProjectConfig::set('users.require2fa', 'all');

    $this->actingAs($user, 'craft')
        ->get('/test-2fa')
        ->assertStatus(200)
        ->assertViewIs('craftcms::_special.setup-2fa')
        ->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private');
});

test('allows user through when 2fa is required and active', function () {
    $user = UserModel::factory()->createElement();

    // Force 2FA requirement for all users
    ProjectConfig::set('users.require2fa', 'all');

    // Mock active method
    $auth = Mockery::mock(app(Auth::class))->makePartial();
    $auth->shouldReceive('hasActiveMethod')->andReturn(true);

    $this->actingAs($user, 'craft')
        ->get('/test-2fa')
        ->assertStatus(200)
        ->assertSee('ok');
});
