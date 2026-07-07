<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition;
use CraftCms\Cms\Auth\OAuth\OAuth;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Tests\TestClasses\OAuth\FakeOAuthProvider;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;

beforeEach(function () {
    Edition::set(Edition::Pro);
    FakeOAuthProvider::reset();
    app(GeneralConfig::class)->oauthProviders([]);

    actingAs(UserModel::first());
    Session::passwordConfirmed();
});

function configureSignInProvider(array $config = []): void
{
    app(GeneralConfig::class)->oauthProviders([
        'test' => array_merge([
            'driver' => FakeOAuthProvider::class,
            'clientId' => 'client-id',
            'clientSecret' => 'client-secret',
            'name' => 'Test Provider',
            'label' => 'Continue with Test OAuth',
        ], $config),
    ]);
}

function signInProviderUser(): User
{
    $user = User::find()->id(Auth::id())->status(null)->addSelect('users.password')->one();

    if (! $user) {
        throw new RuntimeException('Unable to resolve the current test user.');
    }

    return $user;
}

function signInProviderDefinition(): ProviderDefinition
{
    $provider = app(OAuth::class)->getProviderDefinition('test');

    if (! $provider) {
        throw new RuntimeException('Unable to resolve the test OAuth provider.');
    }

    return $provider;
}

function signInProviderCallback(array $attributes = [])
{
    FakeOAuthProvider::$fakeUser = FakeOAuthProvider::fakeUser($attributes);

    return get('oauth/test/callback?context=cp');
}

function signInProviderHasIdentity(string $identity, ?int $userId = null): bool
{
    return DB::table(Table::SSO_IDENTITIES)
        ->where('provider', 'test')
        ->where('identityId', $identity)
        ->when($userId, fn ($query) => $query->where('userId', $userId))
        ->exists();
}

function signInProviderIdentityCount(string $identity): int
{
    return DB::table(Table::SSO_IDENTITIES)
        ->where('provider', 'test')
        ->where('identityId', $identity)
        ->count();
}

function signInProviderRemoveCurrentPassword(): User
{
    $userId = Auth::id();

    if (! $userId) {
        throw new RuntimeException('Unable to resolve the current test user ID.');
    }

    DB::table(Table::USERS)
        ->where('id', $userId)
        ->update(['password' => null]);

    $user = UserModel::query()->find($userId);

    if (! $user) {
        throw new RuntimeException('Unable to reload the current test user.');
    }

    actingAs($user);

    return signInProviderUser();
}

it('requires login', function () {
    configureSignInProvider();

    Auth::logout();

    get(cp_url('myaccount/sign-in-providers'))->assertRedirect();
    get(cp_url('myaccount/sign-in-providers/test/connect'))->assertRedirect();
    delete(cp_url('myaccount/sign-in-providers/test'))->assertRedirect();
});

it('returns 404 when no providers are configured', function () {
    get(cp_url('myaccount/sign-in-providers'))->assertNotFound();
});

it('shows configured providers', function () {
    configureSignInProvider();

    get(cp_url('myaccount/sign-in-providers'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('users/SignInProviders')
            ->where('providers.0.handle', 'test')
            ->where('providers.0.name', 'Test Provider')
            ->where('providers.0.icon', null)
            ->where('providers.0.connected', false)
            ->where('providers.0.canConnect', true)
            ->where('providers.0.disabledReason', null)
            ->has('subnav'));
});

it('shows brand icons for common socialite providers', function () {
    app(GeneralConfig::class)->oauthProviders([
        'company-github' => [
            'driver' => 'github',
            'clientId' => 'client-id',
            'clientSecret' => 'client-secret',
            'name' => 'GitHub',
        ],
    ]);

    get(cp_url('myaccount/sign-in-providers'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('providers.0.handle', 'company-github')
            ->where('providers.0.icon', 'github'));
});

it('uses configured provider icons before driver icons', function () {
    configureSignInProvider([
        'driver' => 'github',
        'icon' => 'custom-provider-icon',
    ]);

    get(cp_url('myaccount/sign-in-providers'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('providers.0.icon', 'custom-provider-icon'));
});

it('shows the account security nav for sso only users', function () {
    configureSignInProvider();

    signInProviderRemoveCurrentPassword();

    get(cp_url('myaccount/sign-in-providers'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('subnav', fn ($subnav) => collect($subnav)->contains(
                fn (array $item) => ($item['label'] ?? null) === 'Account Security' &&
                    collect($item['subnav'] ?? [])->contains(fn (array $subitem) => ($subitem['label'] ?? null) === 'Sign-in Providers') &&
                    collect($item['subnav'] ?? [])->doesntContain(fn (array $subitem) => ($subitem['label'] ?? null) === 'Password & Verification')
            )));
});

it('marks stateless providers as unavailable for connecting', function () {
    configureSignInProvider([
        'stateless' => true,
    ]);

    get(cp_url('myaccount/sign-in-providers'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('providers.0.canConnect', false)
            ->where('providers.0.disabledReason', fn (?string $reason) => str_contains($reason ?? '', 'stateless OAuth')));
});

it('requires an elevated session to connect a provider', function () {
    configureSignInProvider();
    Session::forget('auth.password_confirmed_at');

    get(cp_url('myaccount/sign-in-providers/test/connect'))->assertForbidden();

    expect(session()->has(OAuth::CONNECT_SESSION_KEY))->toBeFalse();
});

it('does not connect stateless providers', function () {
    configureSignInProvider([
        'stateless' => true,
    ]);

    get(cp_url('myaccount/sign-in-providers/test/connect'))
        ->assertRedirect(cp_url('myaccount/sign-in-providers'))
        ->assertSessionHas('error');

    expect(session()->has(OAuth::CONNECT_SESSION_KEY))->toBeFalse();
});

it('connects a provider to the current user', function () {
    configureSignInProvider();
    $user = signInProviderUser();

    get(cp_url('myaccount/sign-in-providers/test/connect'))
        ->assertRedirect('https://provider.test/oauth/authorize');

    signInProviderCallback([
        'id' => 'provider-user-1',
        'email' => 'provider-user-1@example.com',
    ])
        ->assertRedirect(cp_url('myaccount/sign-in-providers'))
        ->assertSessionHas('success');

    expect(Auth::id())->toBe($user->id)
        ->and(signInProviderHasIdentity('provider-user-1', $user->id))->toBeTrue();
});

it('does not duplicate an already connected provider identity', function () {
    configureSignInProvider();
    $user = signInProviderUser();
    app(OAuth::class)->linkIdentity($user, signInProviderDefinition(), 'provider-user-1');

    get(cp_url('myaccount/sign-in-providers/test/connect'));

    signInProviderCallback([
        'id' => 'provider-user-1',
        'email' => 'provider-user-1@example.com',
    ])
        ->assertRedirect(cp_url('myaccount/sign-in-providers'))
        ->assertSessionHas('success');

    expect(signInProviderIdentityCount('provider-user-1'))->toBe(1);
});

it('does not connect an identity that belongs to another user', function () {
    configureSignInProvider();
    $user = signInProviderUser();
    $otherUser = UserModel::factory()->active()->createElement([
        'email' => 'other-provider-user@example.com',
        'username' => 'other-provider-user',
    ]);

    app(OAuth::class)->linkIdentity($otherUser, signInProviderDefinition(), 'provider-user-1');

    get(cp_url('myaccount/sign-in-providers/test/connect'));

    signInProviderCallback([
        'id' => 'provider-user-1',
        'email' => 'provider-user-1@example.com',
    ])
        ->assertRedirect(cp_url('myaccount/sign-in-providers'))
        ->assertSessionHas('error');

    expect(signInProviderHasIdentity('provider-user-1', $otherUser->id))->toBeTrue()
        ->and(signInProviderHasIdentity('provider-user-1', $user->id))->toBeFalse();
});

it('does not replace a different identity for the same provider', function () {
    configureSignInProvider();
    $user = signInProviderUser();
    app(OAuth::class)->linkIdentity($user, signInProviderDefinition(), 'provider-user-1');

    get(cp_url('myaccount/sign-in-providers/test/connect'));

    signInProviderCallback([
        'id' => 'provider-user-2',
        'email' => 'provider-user-2@example.com',
    ])
        ->assertRedirect(cp_url('myaccount/sign-in-providers'))
        ->assertSessionHas('error');

    expect(signInProviderHasIdentity('provider-user-1', $user->id))->toBeTrue()
        ->and(signInProviderHasIdentity('provider-user-2', $user->id))->toBeFalse();
});

it('requires an elevated session to disconnect a provider', function () {
    configureSignInProvider();
    $user = signInProviderUser();
    app(OAuth::class)->linkIdentity($user, signInProviderDefinition(), 'provider-user-1');
    Session::forget('auth.password_confirmed_at');

    delete(cp_url('myaccount/sign-in-providers/test'))->assertForbidden();

    expect(signInProviderHasIdentity('provider-user-1', $user->id))->toBeTrue();
});

it('disconnects only the current user provider identity', function () {
    configureSignInProvider();
    $user = signInProviderRemoveCurrentPassword();
    $otherUser = UserModel::factory()->active()->createElement([
        'email' => 'other-disconnect-user@example.com',
        'username' => 'other-disconnect-user',
    ]);

    app(OAuth::class)->linkIdentity($user, signInProviderDefinition(), 'provider-user-1');
    app(OAuth::class)->linkIdentity($otherUser, signInProviderDefinition(), 'provider-user-2');

    delete(cp_url('myaccount/sign-in-providers/test'))
        ->assertRedirect(cp_url('myaccount/sign-in-providers'))
        ->assertSessionHas('success');

    expect(signInProviderHasIdentity('provider-user-1', $user->id))->toBeFalse()
        ->and(signInProviderHasIdentity('provider-user-2', $otherUser->id))->toBeTrue();
});

it('warns when disconnecting the last primary sign in method', function () {
    configureSignInProvider();
    $user = signInProviderRemoveCurrentPassword();

    app(OAuth::class)->linkIdentity($user, signInProviderDefinition(), 'provider-user-1');

    get(cp_url('myaccount/sign-in-providers'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('providers.0.connected', true)
            ->where('providers.0.disconnectWarning', fn (?string $warning) => str_contains($warning ?? '', 'without a password or connected sign-in provider')));
});
