<?php

declare(strict_types=1);

use craft\helpers\UrlHelper;
use CraftCms\Cms\Auth\Models\Authenticator;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Tests\TestClasses\OAuth\CustomIdentityResolver;
use CraftCms\Cms\Tests\TestClasses\OAuth\CustomUserGroupResolver;
use CraftCms\Cms\Tests\TestClasses\OAuth\CustomUserPopulator;
use CraftCms\Cms\Tests\TestClasses\OAuth\CustomUserResolver;
use CraftCms\Cms\Tests\TestClasses\OAuth\FakeOAuthProvider;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\get;

beforeEach(function () {
    Edition::set(Edition::Pro);
    Cms::config()->isSystemLive = true;
    ProjectConfig::set('users.allowPublicRegistration', true);

    FakeOAuthProvider::reset();
    CustomUserResolver::reset();
    CustomUserGroupResolver::reset();

    app(GeneralConfig::class)->oauthProviders([]);
});

function configureOAuthControllerProvider(array $config = []): void
{
    app(GeneralConfig::class)->oauthProviders([
        'test' => array_merge([
            'driver' => FakeOAuthProvider::class,
            'clientId' => 'client-id',
            'clientSecret' => 'client-secret',
            'label' => 'Continue with Test OAuth',
        ], $config),
    ]);
}

function oauthControllerRedirectUrl(bool $isCpRequest = false, string $provider = 'test'): string
{
    return $isCpRequest
        ? cp_url("oauth/$provider/redirect")
        : "oauth/$provider/redirect";
}

function oauthControllerCallbackUrl(bool $isCpRequest = false, string $provider = 'test'): string
{
    $url = "oauth/$provider/callback";

    return $isCpRequest ? "$url?context=cp" : $url;
}

function oauthControllerLoginUrl(bool $isCpRequest): string
{
    return $isCpRequest
        ? cp_url('login')
        : UrlHelper::siteUrl(app(GeneralConfig::class)->getLoginPath());
}

function completeOAuthControllerCallback(
    array $attributes = [],
    bool $isCpRequest = false,
    string $provider = 'test',
) {
    FakeOAuthProvider::$fakeUser = FakeOAuthProvider::fakeUser($attributes);

    get(oauthControllerRedirectUrl($isCpRequest, $provider));

    return get(oauthControllerCallbackUrl($isCpRequest, $provider));
}

function oauthControllerHasLinkedIdentity(string $provider, string $identity, int $userId): bool
{
    return DB::table(Table::SSO_IDENTITIES)
        ->where('provider', $provider)
        ->where('identityId', $identity)
        ->where('userId', $userId)
        ->exists();
}

function oauthControllerHasGroupAssignment(int $userId, int $groupId): bool
{
    return DB::table(Table::USERGROUPS_USERS)
        ->where('userId', $userId)
        ->where('groupId', $groupId)
        ->exists();
}

describe('redirect flow', function () {
    test('redirect preserves the cp callback context and redirects to the provider', function () {
        configureOAuthControllerProvider();

        get(oauthControllerRedirectUrl(true))
            ->assertRedirect('https://provider.test/oauth/authorize');

        expect(session()->has('auth.context'))->toBeFalse()
            ->and(session('url.intended'))->toContain(app(GeneralConfig::class)->getPostCpLoginRedirect());
    });

    test('unknown providers return 404', function () {
        get('oauth/missing')->assertNotFound();
    });
});

describe('callback flow', function () {
    test('callback creates a new user, links the identity, and assigns groups', function () {
        UserGroups::saveGroup($group = new UserGroup([
            'name' => 'Members',
            'handle' => 'members',
        ]));

        configureOAuthControllerProvider([
            'groups' => [$group->handle],
            'activatesUsers' => true,
        ]);

        completeOAuthControllerCallback([
            'id' => 'provider-user-1',
            'email' => 'oauth-created@example.com',
            'name' => 'OAuth Created',
        ])->assertRedirect();

        $user = User::find()->email('oauth-created@example.com')->status(null)->first();

        expect(Auth::check())->toBeTrue()
            ->and($user)->not()->toBeNull()
            ->and($user->active)->toBeTrue()
            ->and(oauthControllerHasLinkedIdentity('test', 'provider-user-1', $user->id))->toBeTrue()
            ->and(oauthControllerHasGroupAssignment($user->id, $group->id))->toBeTrue();
    });

    test('callback blocks new user creation when policy disallows it', function (array $scenario) {
        ProjectConfig::set('users.allowPublicRegistration', $scenario['allowPublicRegistration']);

        configureOAuthControllerProvider($scenario['providerConfig']);

        completeOAuthControllerCallback([
            'id' => $scenario['identity'],
            'email' => $scenario['email'],
        ])
            ->assertRedirect(oauthControllerLoginUrl(false))
            ->assertSessionHas('error', $scenario['expectedError']);

        expect(User::find()->email($scenario['email'])->status(null)->first())->toBeNull()
            ->and(Auth::check())->toBeFalse();
    })->with([
        'public registration disabled' => [[
            'allowPublicRegistration' => false,
            'providerConfig' => [],
            'identity' => 'provider-user-no-public-registration',
            'email' => 'oauth-disabled@example.com',
            'expectedError' => 'Public registration is not allowed.',
        ]],
        'provider user creation disabled' => [[
            'allowPublicRegistration' => true,
            'providerConfig' => ['createsUsers' => false],
            'identity' => 'provider-user-creates-users-disabled',
            'email' => 'oauth-provider-disabled@example.com',
            'expectedError' => 'This OAuth provider cannot create new users.',
        ]],
    ]);

    test('callback can create a new user when provider user creation is explicitly enabled', function () {
        ProjectConfig::set('users.allowPublicRegistration', false);

        configureOAuthControllerProvider([
            'createsUsers' => true,
            'activatesUsers' => true,
        ]);

        completeOAuthControllerCallback([
            'id' => 'provider-user-creates-users-enabled',
            'email' => 'oauth-provider-enabled@example.com',
            'name' => 'OAuth Provider Enabled',
        ])->assertRedirect();

        $user = User::find()->email('oauth-provider-enabled@example.com')->status(null)->first();

        expect(Auth::check())->toBeTrue()
            ->and($user)->not()->toBeNull()
            ->and(oauthControllerHasLinkedIdentity('test', 'provider-user-creates-users-enabled', $user->id))->toBeTrue();
    });

    test('callback still links an existing user when public registration is disabled', function () {
        ProjectConfig::set('users.allowPublicRegistration', false);

        $user = UserModel::factory()->active()->createElement([
            'email' => 'existing-no-registration@example.com',
            'username' => 'existing-no-registration',
        ]);

        configureOAuthControllerProvider();

        completeOAuthControllerCallback([
            'id' => 'provider-user-existing-no-registration',
            'email' => 'existing-no-registration@example.com',
        ])->assertRedirect();

        expect(Auth::id())->toBe($user->id)
            ->and(oauthControllerHasLinkedIdentity('test', 'provider-user-existing-no-registration', $user->id))->toBeTrue();
    });

    test('callback links an existing user by email fallback', function () {
        $user = UserModel::factory()->active()->createElement([
            'email' => 'existing@example.com',
            'username' => 'existing-user',
        ]);

        configureOAuthControllerProvider();

        completeOAuthControllerCallback([
            'id' => 'provider-user-2',
            'email' => 'existing@example.com',
        ])->assertRedirect();

        expect(Auth::id())->toBe($user->id)
            ->and(oauthControllerHasLinkedIdentity('test', 'provider-user-2', $user->id))->toBeTrue();
    });

    test('auth context determines whether a non-cp user can sign in', function (
        bool $isCpRequest,
        bool $shouldAuthenticate,
        bool $redirectsToCpLogin,
    ) {
        $user = UserModel::factory()->active()->createElement([
            'email' => 'context-user@example.com',
            'username' => 'context-user',
            'admin' => false,
        ]);

        configureOAuthControllerProvider();

        $response = completeOAuthControllerCallback([
            'id' => 'provider-user-context',
            'email' => 'context-user@example.com',
        ], $isCpRequest);

        if ($redirectsToCpLogin) {
            $response->assertRedirect(cp_url('login'));
        } else {
            $response->assertRedirect();
        }

        expect(Auth::check())->toBe($shouldAuthenticate);

        if ($shouldAuthenticate) {
            expect(Auth::id())->toBe($user->id);
        }
    })->with([
        'cp' => [true, false, true],
        'site' => [false, true, false],
    ]);

    test('callback skips the existing 2fa flow and completes login directly', function () {
        $user = User::findOne();

        Authenticator::create([
            'userId' => $user->id,
            'auth2faSecret' => 'secret',
        ]);

        configureOAuthControllerProvider();

        completeOAuthControllerCallback([
            'id' => 'provider-user-5',
            'email' => $user->email,
        ], true)->assertRedirect();

        expect(Auth::id())->toBe($user->id)
            ->and(session('user.id'))->toBeNull();
    });
});

describe('customization', function () {
    test('custom user resolver is used', function () {
        $user = UserModel::factory()->active()->createElement([
            'email' => 'custom-resolver@example.com',
            'username' => 'custom-resolver',
        ]);

        CustomUserResolver::$userId = $user->id;

        configureOAuthControllerProvider([
            'userResolver' => CustomUserResolver::class,
        ]);

        completeOAuthControllerCallback([
            'id' => 'provider-user-6',
            'email' => null,
        ])->assertRedirect();

        expect(Auth::id())->toBe($user->id);
    });

    test('custom identity resolver, user populator, and group resolver are used for new users', function () {
        UserGroups::saveGroup($groupByUid = new UserGroup([
            'name' => 'Custom UID Group',
            'handle' => 'custom-uid-group',
        ]));
        UserGroups::saveGroup($groupByHandle = new UserGroup([
            'name' => 'Custom Handle Group',
            'handle' => 'custom-handle-group',
        ]));

        CustomUserGroupResolver::$groups = [$groupByUid->uid, $groupByHandle->handle];

        configureOAuthControllerProvider([
            'identityResolver' => CustomIdentityResolver::class,
            'userPopulator' => CustomUserPopulator::class,
            'groupResolver' => CustomUserGroupResolver::class,
        ]);

        completeOAuthControllerCallback([
            'id' => 'provider-user-7',
            'email' => null,
            'name' => null,
        ])->assertRedirect();

        $user = User::find()->username('custom-oauth-user')->status(null)->first();

        expect($user)->not()->toBeNull()
            ->and($user->fullName)->toBe('Custom Populated User')
            ->and(oauthControllerHasLinkedIdentity('test', 'custom:provider-user-7', $user->id))->toBeTrue()
            ->and(oauthControllerHasGroupAssignment($user->id, $groupByUid->id))->toBeTrue()
            ->and(oauthControllerHasGroupAssignment($user->id, $groupByHandle->id))->toBeTrue();
    });
});
