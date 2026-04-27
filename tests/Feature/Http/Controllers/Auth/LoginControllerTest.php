<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Http\Controllers\Auth\LoginController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

test('showLogin redirects already authenticated users', function () {
    actingAs(User::findOne());

    get(action([LoginController::class, 'showLogin']))
        ->assertRedirect();
});

test('showLogin shows the login form for guests', function () {
    get(action([LoginController::class, 'showLogin']))
        ->assertOk();
});

test('showLogin renders flashed login errors', function () {
    Craft::$app->getSession()->setFlash('cp-notification-error', ['Authentication failed.', []]);

    get(cp_url('login'))
        ->assertOk()
        ->assertSee('Authentication failed.');
});

test('showLogin redirects to 2fa form when verify parameter is present', function () {
    get(action([LoginController::class, 'showLogin'], ['verify' => 1]))
        ->assertRedirect();
});

test('attemptLogin validates required fields', function () {
    postJson(action([LoginController::class, 'attemptLogin']), [])
        ->assertJsonValidationErrors(['loginName', 'password']);

    postJson(action([LoginController::class, 'attemptLogin']), [
        'loginName' => 'admin',
    ])->assertJsonValidationErrors(['password']);

    postJson(action([LoginController::class, 'attemptLogin']), [
        'password' => 'secret',
    ])->assertJsonValidationErrors(['loginName']);
});

test('attemptLogin fails with wrong password', function () {
    Event::fake([Failed::class]);

    $user = User::findOne();

    postJson(action([LoginController::class, 'attemptLogin']), [
        'loginName' => $user->email,
        'password' => 'wrongpassword',
    ])->assertStatus(400);

    Event::assertDispatched(Failed::class);
});

test('attemptLogin succeeds with valid credentials', function () {
    $user = User::findOne();

    postJson(action([LoginController::class, 'attemptLogin']), [
        'loginName' => $user->email,
        'password' => 'craftcms2018!!',
    ])->assertOk();

    expect(Auth::check())->toBeTrue();
    expect(Auth::id())->toBe($user->id);
});

test('attemptLogin works with username instead of email', function () {
    $user = User::findOne();

    postJson(action([LoginController::class, 'attemptLogin']), [
        'loginName' => $user->username,
        'password' => 'craftcms2018!!',
    ])->assertOk();

    expect(Auth::check())->toBeTrue();
});

test('attemptLogin returns returnUrl on success', function () {
    $user = User::findOne();

    postJson(action([LoginController::class, 'attemptLogin']), [
        'loginName' => $user->email,
        'password' => 'craftcms2018!!',
    ])
        ->assertOk()
        ->assertJsonStructure(['returnUrl']);
});

test('attemptLogin fails for user without password', function () {
    $user = User::findOne();

    DB::table(Table::USERS)->where('id', $user->id)->update(['password' => null]);

    postJson(action([LoginController::class, 'attemptLogin']), [
        'loginName' => $user->email,
        'password' => 'craftcms2018!!',
    ])->assertStatus(400);

    expect(Auth::check())->toBeFalse();
});

test('logout logs the user out and redirects', function () {
    actingAs(User::findOne());

    expect(Auth::check())->toBeTrue();

    get(action([LoginController::class, 'logout']))
        ->assertRedirect();

    expect(Auth::check())->toBeFalse();
});

test('showLoginModal requires email parameter', function () {
    postJson(action([LoginController::class, 'showLoginModal']), [])
        ->assertJsonValidationErrors(['email']);
});

test('showLoginModal returns html for the login modal', function () {
    postJson(action([LoginController::class, 'showLoginModal']), [
        'email' => 'test@example.com',
    ])
        ->assertOk()
        ->assertJsonStructure(['html', 'headHtml', 'bodyHtml']);
});

test('showLoginModal requires email even with forElevatedSession when not impersonating', function () {
    actingAs(User::findOne());

    postJson(action([LoginController::class, 'showLoginModal']), [
        'forElevatedSession' => true,
    ])
        ->assertJsonValidationErrors(['email']);
});

test('attemptLogin accepts rememberMe parameter', function () {
    $user = User::findOne();

    postJson(action([LoginController::class, 'attemptLogin']), [
        'loginName' => $user->email,
        'password' => 'craftcms2018!!',
        'rememberMe' => true,
    ])->assertOk();

    expect(Auth::check())->toBeTrue();
});

test('attemptLogin returns user model on success', function () {
    $user = User::findOne();

    postJson(action([LoginController::class, 'attemptLogin']), [
        'loginName' => $user->email,
        'password' => 'craftcms2018!!',
    ])
        ->assertOk()
        ->assertJsonPath('modelName', 'user');
});

test('attemptLogin dispatches Failed event on wrong credentials', function () {
    Event::fake([Failed::class]);

    $user = User::findOne();

    postJson(action([LoginController::class, 'attemptLogin']), [
        'loginName' => $user->email,
        'password' => 'wrongpassword',
    ]);

    Event::assertDispatched(fn (Failed $event) => $event->user->id === $user->id
        && $event->credentials['loginName'] === $user->email);
});
