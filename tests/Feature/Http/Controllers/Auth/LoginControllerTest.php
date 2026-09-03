<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Auth\Events\LoginUserRetrieved;
use CraftCms\Cms\Auth\Events\LoginUserRetrieving;
use CraftCms\Cms\Auth\LoginRateLimiter;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\Auth\LoginController;
use CraftCms\Cms\Tests\TestClasses\OAuth\FakeOAuthProvider;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Auth\Events\Failed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
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

test('showLogin includes configured OAuth buttons', function () {
    Edition::set(Edition::Pro);

    app(GeneralConfig::class)->oauthProviders([
        'test' => [
            'driver' => FakeOAuthProvider::class,
            'clientId' => 'client-id',
            'clientSecret' => 'client-secret',
            'label' => 'Continue with Test OAuth',
        ],
    ]);

    get(cp_url(CpAuthPath::Login->value))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('auth/Login')
            ->where('oauthLoginButtons.0', fn (string $button) => str_contains($button, 'Continue with Test OAuth') &&
                str_contains($button, 'oauth/test/redirect')));
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
    ])->assertBadRequest();

    Event::assertDispatched(Failed::class);
});

test('attemptLogin counts a wrong password once', function () {
    Cms::config()->maxInvalidLogins = 10;

    $user = User::findOne();

    postJson(action([LoginController::class, 'attemptLogin']), [
        'loginName' => $user->email,
        'password' => 'wrongpassword',
    ])->assertBadRequest();

    expect(UserModel::findOrFail($user->id)->invalidLoginCount)->toBe(1);
});

test('attemptLogin is limited to five failed attempts per minute', function () {
    $user = User::findOne();

    foreach (range(1, 5) as $attempt) {
        postJson(action([LoginController::class, 'attemptLogin']), [
            'loginName' => $attempt % 2 === 0 ? mb_strtoupper($user->email) : $user->email,
            'password' => 'wrongpassword',
        ])->assertBadRequest();
    }

    postJson(action([LoginController::class, 'attemptLogin']), [
        'loginName' => $user->email,
        'password' => 'wrongpassword',
    ])->assertTooManyRequests();
});

test('attemptLogin limits full-page login failures', function () {
    $user = User::findOne();

    foreach (range(1, 5) as $attempt) {
        post(action([LoginController::class, 'attemptLogin']), [
            'loginName' => $user->email,
            'password' => 'wrongpassword',
        ])->assertRedirect();
    }

    post(action([LoginController::class, 'attemptLogin']), [
        'loginName' => $user->email,
        'password' => 'wrongpassword',
    ])->assertTooManyRequests();
});

test('attemptLogin clears failed attempts after valid credentials', function () {
    $user = User::findOne();

    postJson(action([LoginController::class, 'attemptLogin']), [
        'loginName' => $user->email,
        'password' => 'wrongpassword',
    ])->assertBadRequest();

    postJson(action([LoginController::class, 'attemptLogin']), [
        'loginName' => $user->email,
        'password' => 'craftcms2018!!',
    ])->assertOk();

    Auth::logout();

    foreach (range(1, 5) as $attempt) {
        postJson(action([LoginController::class, 'attemptLogin']), [
            'loginName' => $user->email,
            'password' => 'wrongpassword',
        ])->assertBadRequest();
    }
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
    ])->assertBadRequest();

    expect(Auth::check())->toBeFalse();
});

test('logout logs the user out and redirects', function (string $method) {
    actingAs(User::findOne());

    expect(Auth::check())->toBeTrue();

    $this->$method(action([LoginController::class, 'logout']))
        ->assertRedirect();

    expect(Auth::check())->toBeFalse();
})->with(['get', 'post']);

test('logout redirects to the post-logout redirect, not back to the previous page', function () {
    Cms::config()->postLogoutRedirect = '';

    actingAs(User::findOne());

    // Even when arriving from a page, logout must not fall through to back().
    $response = $this->from('https://localhost/members/dashboard')
        ->post('/'.Cms::config()->getLogoutPath())
        ->assertRedirect();

    expect($response->headers->get('Location'))->toBe('https://localhost/');
});

test('logout honors a configured post-logout redirect', function () {
    Cms::config()->postLogoutRedirect = 'goodbye';

    actingAs(User::findOne());

    $this->post('/'.Cms::config()->getLogoutPath())
        ->assertRedirect('https://localhost/goodbye');
});

test('logout does not stash itself as the post-login return URL', function () {
    get(cp_url(CpAuthPath::Logout->value))
        ->assertRedirect(cp_url(CpAuthPath::Login->value));

    expect(session('url.intended'))->toBeNull();
});

test('logging in ignores a stashed logout return URL', function () {
    $user = User::findOne();

    session(['url.intended' => cp_url(CpAuthPath::Logout->value)]);

    postJson(action([LoginController::class, 'attemptLogin']), [
        'loginName' => $user->email,
        'password' => 'craftcms2018!!',
    ])
        ->assertOk()
        ->assertJsonPath('returnUrl', cp_url(Cms::config()->getPostCpLoginRedirect()));

    expect(session('url.intended'))->toBeNull();
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

    Event::assertDispatched(fn (Failed $event) => $event->user?->getAuthIdentifier() === $user->id
        && $event->credentials['loginName'] === $user->email);
});

test('attemptLogin validates loginName as email when useEmailAsUsername is true', function () {
    Cms::config()->useEmailAsUsername = true;

    postJson(action([LoginController::class, 'attemptLogin']), [
        'loginName' => 'not-an-email',
        'password' => 'craftcms2018!!',
    ])->assertJsonValidationErrors(['loginName']);
});

test('attemptLogin accepts username when useEmailAsUsername is false', function () {
    Cms::config()->useEmailAsUsername = false;

    $user = User::findOne();

    postJson(action([LoginController::class, 'attemptLogin']), [
        'loginName' => $user->username,
        'password' => 'craftcms2018!!',
    ])->assertOk();

    expect(Auth::check())->toBeTrue();
});

test('login routes are registered for localized loginPath values', function () {
    Cms::config()->loginPath = ['siteWithCustomPath' => 'aanmelden'];

    Route::middleware(['web', 'craft', 'craft.web'])->group(dirname(__DIR__, 5).'/routes/web.php');

    $route = Route::getRoutes()->match(Request::create('/aanmelden', 'POST'));
    $user = User::findOne();

    expect($route->middleware())->toContain('throttle:'.LoginRateLimiter::NAME);

    postJson('/aanmelden', [
        'loginName' => $user->email,
        'password' => 'craftcms2018!!',
    ])->assertOk();

    expect(Auth::check())->toBeTrue();
});

test('attemptLogin respects LoginUserRetrieving event', function () {
    $user = UserModel::factory()->admin()->create([
        'email' => 'event-user@example.com',
    ]);

    Event::listen(LoginUserRetrieving::class, function (LoginUserRetrieving $event) use ($user) {
        $event->user = $user;
    });

    postJson(action([LoginController::class, 'attemptLogin']), [
        'loginName' => 'some-other-user',
        'password' => 'password',
    ])->assertOk();

    expect(Auth::id())->toBe($user->id);
});

test('attemptLogin respects LoginUserRetrieved event', function () {
    $original = UserModel::factory()->admin()->create([
        'email' => 'original@example.com',
    ]);
    $replacement = UserModel::factory()->admin()->create([
        'email' => 'replacement@example.com',
    ]);

    Event::listen(LoginUserRetrieved::class, function (LoginUserRetrieved $event) use ($replacement) {
        $event->user = $replacement;
    });

    postJson(action([LoginController::class, 'attemptLogin']), [
        'loginName' => $original->email,
        'password' => 'password',
    ])->assertOk();

    expect(Auth::id())->toBe($replacement->id);
});
