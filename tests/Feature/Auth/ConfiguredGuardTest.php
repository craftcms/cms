<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\AuthMethods;
use CraftCms\Cms\Auth\AuthServiceProvider;
use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Auth\LoginController;
use CraftCms\Cms\Http\Controllers\Gql\TokensController;
use CraftCms\Cms\Http\Middleware\UseCraftAuthGuard;
use CraftCms\Cms\User\Models\User;
use Illuminate\Auth\GenericUser;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Routing\Router;
use Illuminate\Session\Middleware\AuthenticateSession as LaravelAuthenticateSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\currentUser;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    ConfiguredGuardTestUserProvider::$user = null;

    config()->set([
        'auth.defaults.guard' => 'host',
        'auth.guards.host' => [
            'driver' => 'session',
            'provider' => 'host-users',
        ],
        'auth.providers.host-users' => [
            'driver' => 'test-host-users',
        ],
    ]);

    Auth::provider('test-host-users', fn () => new ConfiguredGuardTestUserProvider);
    Auth::forgetGuards();

    Cms::config()->authGuard = 'craft';
});

it('keeps Craft and host authentication isolated', function () {
    $hostUser = new GenericUser(['id' => 42, 'password' => '']);
    ConfiguredGuardTestUserProvider::$user = $hostUser;
    Auth::guard('host')->login($hostUser);

    $craftUser = User::firstOrFail();

    postJson(action([LoginController::class, 'attemptLogin']), [
        'loginName' => $craftUser->email,
        'password' => 'craftcms2018!!',
    ])->assertOk();

    expect(Auth::getDefaultDriver())->toBe('host')
        ->and(Auth::guard('host')->user())->toBe($hostUser)
        ->and(Auth::guard('craft')->id())->toBe($craftUser->id)
        ->and(currentUser()?->getCraftUserId())->toBe($craftUser->id);
});

it('does not treat a host user as authenticated on protected Craft routes', function () {
    $hostUser = new GenericUser(['id' => 42, 'password' => '']);
    ConfiguredGuardTestUserProvider::$user = $hostUser;
    Auth::guard('host')->login($hostUser);

    get(cp_url('dashboard'))->assertRedirect(cp_url('login'));

    expect(Auth::getDefaultDriver())->toBe('host')
        ->and(Auth::guard('host')->user())->toBe($hostUser)
        ->and(currentUser())->toBeNull();
});

it('logs out only the Craft guard', function () {
    $hostUser = new GenericUser(['id' => 42, 'password' => '']);
    ConfiguredGuardTestUserProvider::$user = $hostUser;
    Auth::guard('host')->login($hostUser);
    Auth::guard('craft')->login(User::firstOrFail());

    post(action([LoginController::class, 'logout']))->assertRedirect();

    expect(Auth::guard('host')->user())->toBe($hostUser)
        ->and(Auth::guard('craft')->guest())->toBeTrue();
});

it('restores the host default guard when Craft middleware throws', function () {
    expect(fn () => app(UseCraftAuthGuard::class)->handle(
        request(),
        fn () => throw new RuntimeException('Expected failure'),
    ))->toThrow(RuntimeException::class, 'Expected failure');

    expect(Auth::getDefaultDriver())->toBe('host');
});

it('uses an independently configured password broker', function () {
    config()->set('auth.passwords.craft-test', [
        ...config('auth.passwords.users'),
        'provider' => 'craft',
    ]);
    Cms::config()->authPasswordBroker = 'craft-test';
    expect(Password::broker(Cms::config()->getAuthPasswordBroker()))->toBe(Password::broker('craft-test'))
        ->not->toBe(Password::broker());
});

it('requires Craft password confirmation on protected Craft routes', function () {
    app(Router::class)->aliasMiddleware('password.confirm', RequirePassword::class);
    Auth::guard('craft')->login(User::firstOrFail());
    Session::put('auth.password_confirmed_at', now()->unix());
    Session::forget('auth.craft.password_confirmed_at');

    postJson(action([TokensController::class, 'accessToken'], ['tokenId' => 999999]))
        ->assertStatus(423);
});

it('accepts Craft password confirmation on protected Craft routes', function () {
    app(Router::class)->aliasMiddleware('password.confirm', RequirePassword::class);
    Auth::guard('craft')->login(User::firstOrFail());
    Session::forget('auth.password_confirmed_at');
    Session::put('auth.craft.password_confirmed_at', now()->unix());

    postJson(action([TokensController::class, 'accessToken'], ['tokenId' => 999999]))
        ->assertNotFound();
});

it('preserves the host session when the Craft password hash changes', function () {
    Route::get('_test/craft-session-auth', fn () => response()->noContent())
        ->middleware(['craft', 'craft.web']);

    $hostUser = new GenericUser(['id' => 42, 'password' => 'host-password-hash']);
    ConfiguredGuardTestUserProvider::$user = $hostUser;
    Auth::guard('host')->login($hostUser);

    $craftUser = User::firstOrFail();
    Auth::guard('craft')->login($craftUser);
    app(AuthMethods::class)->setUser($craftUser);
    app(Impersonation::class)->setImpersonatorId($craftUser->id);

    Session::put([
        'host-state' => 'preserved',
        'password_hash_craft' => 'stale-password-hash',
        'auth.craft.password_confirmed_at' => now()->unix(),
        SessionAuth::$authAccessParam => ['test-action'],
    ]);

    LaravelAuthenticateSession::redirectUsing(fn () => null);

    try {
        getJson('_test/craft-session-auth')->assertUnauthorized();
    } finally {
        LaravelAuthenticateSession::redirectUsing(fn () => route('login'));
    }

    Auth::forgetGuards();

    expect(Auth::guard('host')->user()?->getAuthIdentifier())->toBe(42)
        ->and(Auth::guard('craft')->guest())->toBeTrue()
        ->and(Session::get('host-state'))->toBe('preserved')
        ->and(Session::has('password_hash_craft'))->toBeFalse()
        ->and(Session::has('auth.craft.password_confirmed_at'))->toBeFalse()
        ->and(Session::has(SessionAuth::$authAccessParam))->toBeFalse()
        ->and(Session::has('__impersonator_id'))->toBeFalse()
        ->and(app(Impersonation::class)->getImpersonatorId())->toBeNull()
        ->and(Session::has('user.id'))->toBeFalse()
        ->and(Session::has('user.login_id'))->toBeFalse()
        ->and(Session::has('user.remember'))->toBeFalse()
        ->and(Session::has('user.pending_2fa_at'))->toBeFalse();
});

it('fills missing Craft guard and provider configuration defaults', function () {
    $guard = config('auth.guards.craft');
    $provider = config('auth.providers.craft');

    try {
        config()->set('auth.guards.craft', ['remember' => 60]);
        config()->set('auth.providers.craft', ['model' => User::class]);

        new AuthServiceProvider(app())->register();

        expect(config('auth.guards.craft'))->toBe([
            'driver' => 'session',
            'provider' => 'craft',
            'remember' => 60,
        ])->and(config('auth.providers.craft'))->toBe([
            'driver' => 'eloquent',
            'model' => User::class,
        ]);
    } finally {
        config()->set('auth.guards.craft', $guard);
        config()->set('auth.providers.craft', $provider);
    }
});

class ConfiguredGuardTestUserProvider implements UserProvider
{
    public static ?Authenticatable $user = null;

    public function retrieveById($identifier): ?Authenticatable
    {
        return self::$user?->getAuthIdentifier() === $identifier ? self::$user : null;
    }

    public function retrieveByToken($identifier, #[SensitiveParameter] $token): ?Authenticatable
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, #[SensitiveParameter] $token): void {}

    public function retrieveByCredentials(#[SensitiveParameter] array $credentials): ?Authenticatable
    {
        return null;
    }

    public function validateCredentials(Authenticatable $user, #[SensitiveParameter] array $credentials): bool
    {
        return false;
    }

    public function rehashPasswordIfRequired(Authenticatable $user, #[SensitiveParameter] array $credentials, bool $force = false): void {}
}
