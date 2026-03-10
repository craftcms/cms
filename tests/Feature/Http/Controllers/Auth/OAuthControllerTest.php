<?php

declare(strict_types=1);

use craft\helpers\UrlHelper;
use CraftCms\Cms\Auth\Models\SsoIdentity;
use CraftCms\Cms\Auth\OAuth\OAuth;
use CraftCms\Cms\Auth\OAuth\Provider;
use CraftCms\Cms\Auth\OAuth\ProviderProfile;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Auth\LoginController;
use CraftCms\Cms\Tests\TestClasses\Auth\FakeSocialiteProvider;
use CraftCms\Cms\Tests\TestClasses\Auth\MarketingProvider;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Two\User as SocialiteUser;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

beforeEach(function () {
    Cms::config()->isSystemLive = true;

    Cms::config()->oAuthProviders = [
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

    app(SocialiteFactory::class)->extend('fake-socialite', fn ($app) => new FakeSocialiteProvider('fake-socialite'));
    app(SocialiteFactory::class)->forgetDrivers();
});

it('discovers configured socialite providers', function () {
    expect(app(OAuth::class)->getProviders()->keys()->all())
        ->toBe(['marketing']);
});

it('discovers configured provider definition classes', function () {
    Cms::config()->oAuthProviders = [
        MarketingProvider::class,
    ];

    $providers = app(OAuth::class)->getProviders();

    expect($providers->keys()->all())->toBe(['marketing']);
    expect($providers->get('marketing'))->toBeInstanceOf(Provider::class);
});

it('discovers fluent provider definitions', function () {
    Cms::config()->oAuthProviders = [
        new Provider('marketing')
            ->driver('fake-socialite')
            ->name('Marketing SSO')
            ->clientId('marketing-client')
            ->clientSecret('marketing-secret')
            ->scopes(['openid', 'email'])
            ->with(['prompt' => 'login'])
            ->activatesUsers()
            ->stateless(),
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
    Cms::config()->oAuthProviders = [
        new Provider('marketing')
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
    Cms::config()->oAuthProviders = [
        'marketing' => [
            'driver' => 'fake-socialite',
            'name' => 'Marketing SSO',
            'activatesUsers' => true,
        ],
    ];

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

it('does not activate existing users unless the provider explicitly allows it', function () {
    $user = UserModel::factory()->create([
        'email' => 'suspended@example.com',
        'username' => 'suspended-user',
        'suspended' => true,
        'active' => false,
    ])->asElement();

    SsoIdentity::query()->create([
        'provider' => 'marketing',
        'identityId' => 'socialite-suspended-user',
        'userId' => $user->id,
    ]);

    FakeSocialiteProvider::fake('fake-socialite', fakeSocialiteUser(
        id: 'socialite-suspended-user',
        email: 'suspended@example.com',
        name: 'Suspended User',
    ));

    get(route('craft.auth.socialite.redirect', ['provider' => 'marketing']));

    getJson(route('craft.auth.socialite.callback', ['provider' => 'marketing']))
        ->assertStatus(400)
        ->assertJsonPath('errorCode', 'account_suspended');

    expect(auth('craft')->guest())->toBeTrue();
});

it('redirects site OAuth failures back to the originating login page', function () {
    $user = UserModel::factory()->create([
        'email' => 'site-suspended@example.com',
        'username' => 'site-suspended-user',
        'suspended' => true,
        'active' => false,
    ])->asElement();

    SsoIdentity::query()->create([
        'provider' => 'marketing',
        'identityId' => 'socialite-site-suspended-user',
        'userId' => $user->id,
    ]);

    FakeSocialiteProvider::fake('fake-socialite', fakeSocialiteUser(
        id: 'socialite-site-suspended-user',
        email: 'site-suspended@example.com',
        name: 'Site Suspended User',
    ));

    $loginUrl = UrlHelper::siteUrl(Cms::config()->getLoginPath());

    get(route('craft.auth.socialite.redirect', ['provider' => 'marketing']));

    get(route('craft.auth.socialite.callback', ['provider' => 'marketing']))
        ->assertRedirect($loginUrl)
        ->assertSessionHas('errorCode', 'account_suspended');

    expect(Craft::$app->getSession()->getError())->toBe('Account suspended.');
    expect(auth('craft')->guest())->toBeTrue();
});

it('renders the flashed OAuth failure message on the site login page', function () {
    $user = UserModel::factory()->create([
        'email' => 'site-login-message@example.com',
        'username' => 'site-login-message-user',
        'suspended' => true,
        'active' => false,
    ])->asElement();

    SsoIdentity::query()->create([
        'provider' => 'marketing',
        'identityId' => 'socialite-site-login-message-user',
        'userId' => $user->id,
    ]);

    FakeSocialiteProvider::fake('fake-socialite', fakeSocialiteUser(
        id: 'socialite-site-login-message-user',
        email: 'site-login-message@example.com',
        name: 'Site Login Message User',
    ));

    $loginUrl = UrlHelper::siteUrl(Cms::config()->getLoginPath());

    get(route('craft.auth.socialite.redirect', ['provider' => 'marketing']));

    get(route('craft.auth.socialite.callback', ['provider' => 'marketing']))
        ->assertRedirect($loginUrl);

    get(action([LoginController::class, 'showLogin']))
        ->assertOk()
        ->assertSee('Account suspended.');
});

it('redirects cp OAuth failures back to the originating login page', function () {
    $user = UserModel::factory()->create([
        'email' => 'cp-suspended@example.com',
        'username' => 'cp-suspended-user',
        'suspended' => true,
        'active' => false,
        'admin' => true,
    ])->asElement();

    SsoIdentity::query()->create([
        'provider' => 'marketing',
        'identityId' => 'socialite-cp-suspended-user',
        'userId' => $user->id,
    ]);

    FakeSocialiteProvider::fake('fake-socialite', fakeSocialiteUser(
        id: 'socialite-cp-suspended-user',
        email: 'cp-suspended@example.com',
        name: 'CP Suspended User',
    ));

    $loginUrl = UrlHelper::cpUrl('login');

    get(route('craft.auth.socialite.redirect', ['provider' => 'marketing', 'cp' => 1]));

    get(route('craft.auth.socialite.callback', ['provider' => 'marketing', 'cp' => 1]))
        ->assertRedirect($loginUrl)
        ->assertSessionHas('errorCode', 'account_suspended');

    expect(Craft::$app->getSession()->getError())->toBe('Account suspended.');
    expect(auth('craft')->guest())->toBeTrue();
});

it('can activate existing users when the provider is trusted', function () {
    Cms::config()->oAuthProviders = [
        'marketing' => [
            'driver' => 'fake-socialite',
            'name' => 'Marketing SSO',
            'activatesUsers' => true,
        ],
    ];

    $user = UserModel::factory()->create([
        'email' => 'trusted@example.com',
        'username' => 'trusted-user',
        'suspended' => true,
        'active' => false,
    ])->asElement();

    SsoIdentity::query()->create([
        'provider' => 'marketing',
        'identityId' => 'socialite-trusted-user',
        'userId' => $user->id,
    ]);

    FakeSocialiteProvider::fake('fake-socialite', fakeSocialiteUser(
        id: 'socialite-trusted-user',
        email: 'trusted@example.com',
        name: 'Trusted User',
    ));

    get(route('craft.auth.socialite.redirect', ['provider' => 'marketing']));

    get(route('craft.auth.socialite.callback', ['provider' => 'marketing']))
        ->assertRedirect(UrlHelper::siteUrl(Cms::config()->getPostLoginRedirect()));

    $user = UserModel::findOrFail($user->id)->asElement();

    expect(auth('craft')->id())->toBe($user->id);
    expect($user->getStatus())->toBe(UserElement::STATUS_ACTIVE);
});

it('passes assignUserGroups arguments as groups first and profile second', function () {
    $seen = [];

    Cms::config()->oAuthProviders = [
        'marketing' => [
            'driver' => 'fake-socialite',
            'name' => 'Marketing SSO',
            'activatesUsers' => true,
            'assignUserGroups' => function (array $groupIds, ProviderProfile $profile) use (&$seen) {
                $seen = [$groupIds, $profile->email];

                return $groupIds;
            },
        ],
    ];

    FakeSocialiteProvider::fake('fake-socialite', fakeSocialiteUser(
        id: 'socialite-groups',
        email: 'groups@example.com',
        name: 'Groups User',
    ));

    get(route('craft.auth.socialite.redirect', [
        'provider' => 'marketing',
        'returnUrl' => 'https://example.com/after-login',
    ]));

    get(route('craft.auth.socialite.callback', ['provider' => 'marketing']))
        ->assertRedirect('https://example.com/after-login');

    expect($seen)->toBe([[], 'groups@example.com']);
});

it('returns json success data on callback', function () {
    Cms::config()->oAuthProviders = [
        'marketing' => [
            'driver' => 'fake-socialite',
            'name' => 'Marketing SSO',
            'activatesUsers' => true,
        ],
    ];

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

function fakeSocialiteUser(
    string $id,
    ?string $email,
    ?string $name = null,
    ?string $nickname = null,
): SocialiteUser {
    return new SocialiteUser()
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
