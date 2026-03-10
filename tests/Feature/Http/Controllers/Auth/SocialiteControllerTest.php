<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\Models\SsoIdentity;
use CraftCms\Cms\Auth\OAuth\ProviderDefinition;
use CraftCms\Cms\Auth\OAuth\OAuth;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Tests\TestClasses\Auth\FakeSocialiteProvider;
use CraftCms\Cms\Tests\TestClasses\Auth\MarketingProviderDefinition;
use CraftCms\Cms\User\Models\User as UserModel;
use craft\helpers\UrlHelper;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

beforeEach(function () {
    Edition::set(Edition::Pro);
    Cms::config()->isSystemLive = true;

    Cms::config()->socialiteProviders = [
        'marketing' => [
            'driver' => 'fake-socialite',
            'name' => 'Marketing SSO',
            'scopes' => ['openid', 'email'],
            'with' => ['prompt' => 'login'],
            'stateless' => true,
        ],
    ];

    Config::set('services.marketing', [
        'client_id' => 'marketing-client',
        'client_secret' => 'marketing-secret',
    ]);

    FakeSocialiteProvider::reset();
    Socialite::extend('fake-socialite', fn ($app) => new FakeSocialiteProvider('fake-socialite'));

    if (method_exists(app(SocialiteFactory::class), 'forgetDrivers')) {
        app(SocialiteFactory::class)->forgetDrivers();
    }
});

it('discovers configured socialite providers', function () {
    expect(app(OAuth::class)->getProviders()->keys()->all())
        ->toBe(['marketing']);
});

it('discovers configured provider definition classes', function () {
    Cms::config()->socialiteProviders = [
        MarketingProviderDefinition::class,
    ];

    $providers = app(OAuth::class)->getProviders();

    expect($providers->keys()->all())->toBe(['marketing']);
    expect($providers->get('marketing'))->toBeInstanceOf(ProviderDefinition::class);
});

it('discovers fluent provider definitions', function () {
    Cms::config()->socialiteProviders = [
        (new ProviderDefinition('marketing'))
            ->driver('fake-socialite')
            ->name('Marketing SSO')
            ->clientId('marketing-client')
            ->clientSecret('marketing-secret')
            ->scopes(['openid', 'email'])
            ->with(['prompt' => 'login'])
            ->stateless(true),
    ];

    $provider = app(OAuth::class)->getProvider('marketing');

    expect($provider->name)->toBe('Marketing SSO');
    expect($provider->clientId)->toBe('marketing-client');
    expect($provider->clientSecret)->toBe('marketing-secret');
    expect($provider->scopes)->toBe(['openid', 'email']);
    expect($provider->with)->toBe(['prompt' => 'login']);
    expect($provider->stateless)->toBeTrue();
});

it('renders cp socialite buttons on the login page', function () {
    get('/'.Cms::config()->cpTrigger.'/login')
        ->assertOk()
        ->assertSee('Sign in with Marketing SSO');
});

it('redirects through the configured socialite driver', function () {
    get(route('craft.auth.socialite.redirect', [
        'provider' => 'marketing',
        'returnUrl' => 'https://example.com/after-login',
    ]))
        ->assertRedirect('/oauth/fake-socialite');

    expect(session('url.intended'))->toBe('https://example.com/after-login');
    expect(FakeSocialiteProvider::$scopes['fake-socialite'])->toBe(['openid', 'email']);
    expect(FakeSocialiteProvider::$with['fake-socialite'])->toBe(['prompt' => 'login']);
    expect(FakeSocialiteProvider::$stateless['fake-socialite'])->toBeTrue();
    expect(FakeSocialiteProvider::$configs['fake-socialite'])->toMatchArray([
        'client_id' => 'marketing-client',
        'client_secret' => 'marketing-secret',
        'redirect' => route('craft.auth.socialite.callback', ['provider' => 'marketing']),
    ]);
});

it('uses provider definition credentials and redirect overrides when configured', function () {
    Cms::config()->socialiteProviders = [
        (new ProviderDefinition('marketing'))
            ->driver('fake-socialite')
            ->name('Marketing SSO')
            ->clientId('configured-client')
            ->clientSecret('configured-secret')
            ->redirectUrl('https://example.com/custom-callback')
            ->with(['prompt' => 'consent', 'login_hint' => 'user@example.com']),
    ];

    get(route('craft.auth.socialite.redirect', [
        'provider' => 'marketing',
        'returnUrl' => 'https://example.com/after-login',
    ]))
        ->assertRedirect('/oauth/fake-socialite');

    expect(FakeSocialiteProvider::$configs['fake-socialite'])->toMatchArray([
        'client_id' => 'configured-client',
        'client_secret' => 'configured-secret',
        'redirect' => 'https://example.com/custom-callback',
    ]);
    expect(FakeSocialiteProvider::$with['fake-socialite'])->toBe([
        'prompt' => 'consent',
        'login_hint' => 'user@example.com',
    ]);
});

it('redirects CP users back to the control panel after callback', function () {
    $user = UserModel::factory()->create([
        'email' => 'cp-user@example.com',
        'username' => 'cp-user',
        'admin' => true,
    ])->asElement();

    FakeSocialiteProvider::fake('fake-socialite', fakeSocialiteUser(
        id: 'socialite-cp-user',
        email: 'cp-user@example.com',
        name: 'CP User',
    ));

    get(route('craft.auth.socialite.redirect', [
        'provider' => 'marketing',
        'cp' => 1,
    ]))
        ->assertRedirect('/oauth/fake-socialite');

    expect(FakeSocialiteProvider::$configs['fake-socialite'])->toMatchArray([
        'redirect' => route('craft.auth.socialite.callback', ['provider' => 'marketing', 'cp' => 1]),
    ]);

    get(route('craft.auth.socialite.callback', ['provider' => 'marketing', 'cp' => 1]))
        ->assertRedirect(UrlHelper::cpUrl(Cms::config()->getPostCpLoginRedirect()));

    expect(auth('craft')->id())->toBe($user->id);
});

it('creates a user and links the identity on callback', function () {
    FakeSocialiteProvider::fake('fake-socialite', fakeSocialiteUser(
        id: 'socialite-123',
        email: 'socialite@example.com',
        name: 'Socialite User',
    ));

    get(route('craft.auth.socialite.redirect', [
        'provider' => 'marketing',
        'returnUrl' => 'https://example.com/after-login',
    ]));

    get(route('craft.auth.socialite.callback', ['provider' => 'marketing']))
        ->assertRedirect('https://example.com/after-login');

    $user = UserModel::query()->where('email', 'socialite@example.com')->firstOrFail()->asElement();

    expect(auth('craft')->id())->toBe($user->id);
    expect($user->getHasSsoIdentity())->toBeTrue();
    expect(SsoIdentity::query()->where([
        'provider' => 'marketing',
        'identityId' => 'socialite-123',
        'userId' => $user->id,
    ])->exists())->toBeTrue();
});

it('reuses an existing linked identity on callback', function () {
    $user = UserModel::factory()->create([
        'email' => 'existing-linked@example.com',
        'username' => 'existing-linked',
    ])->asElement();

    SsoIdentity::query()->create([
        'provider' => 'marketing',
        'identityId' => 'socialite-linked',
        'userId' => $user->id,
    ]);

    FakeSocialiteProvider::fake('fake-socialite', fakeSocialiteUser(
        id: 'socialite-linked',
        email: 'different@example.com',
        name: 'Linked User',
    ));

    get(route('craft.auth.socialite.redirect', [
        'provider' => 'marketing',
        'returnUrl' => 'https://example.com/after-login',
    ]));

    get(route('craft.auth.socialite.callback', ['provider' => 'marketing']))
        ->assertRedirect('https://example.com/after-login');

    expect(auth('craft')->id())->toBe($user->id);
    expect(SsoIdentity::query()->where('provider', 'marketing')->count())->toBe(1);
});

it('falls back to email lookup when no identity exists', function () {
    $user = UserModel::factory()->create([
        'email' => 'email-match@example.com',
        'username' => 'email-match',
    ])->asElement();

    FakeSocialiteProvider::fake('fake-socialite', fakeSocialiteUser(
        id: 'socialite-email-match',
        email: 'email-match@example.com',
        name: 'Email Match',
    ));

    get(route('craft.auth.socialite.redirect', [
        'provider' => 'marketing',
        'returnUrl' => 'https://example.com/after-login',
    ]));

    get(route('craft.auth.socialite.callback', ['provider' => 'marketing']))
        ->assertRedirect('https://example.com/after-login');

    expect(auth('craft')->id())->toBe($user->id);
    expect(SsoIdentity::query()->where([
        'provider' => 'marketing',
        'identityId' => 'socialite-email-match',
        'userId' => $user->id,
    ])->exists())->toBeTrue();
});

it('returns json success data on callback', function () {
    FakeSocialiteProvider::fake('fake-socialite', fakeSocialiteUser(
        id: 'socialite-json',
        email: 'json@example.com',
        name: 'Json User',
    ));

    session(['url.intended' => 'https://example.com/json-login']);

    getJson(route('craft.auth.socialite.callback', ['provider' => 'marketing']))
        ->assertOk()
        ->assertJsonPath('modelName', 'user')
        ->assertJsonPath('returnUrl', 'https://example.com/json-login');
});

function fakeSocialiteUser(string $id, ?string $email, ?string $name = null, ?string $nickname = null): SocialiteUser
{
    return (new SocialiteUser)
        ->setRaw(array_filter([
            'sub' => $id,
            'email' => $email,
            'name' => $name,
            'nickname' => $nickname,
        ], fn (mixed $value) => $value !== null))
        ->map([
            'id' => $id,
            'email' => $email,
            'name' => $name,
            'nickname' => $nickname,
            'avatar' => null,
        ]);
}
