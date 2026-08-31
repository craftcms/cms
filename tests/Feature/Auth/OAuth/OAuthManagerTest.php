<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\OAuth\OAuth;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Tests\TestClasses\OAuth\CustomButtonRenderer;
use CraftCms\Cms\Tests\TestClasses\OAuth\CustomUserGroupResolver;
use CraftCms\Cms\Tests\TestClasses\OAuth\FakeOAuthProvider;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Config;

use function Pest\Laravel\startSession;

beforeEach(function () {
    Edition::set(Edition::Pro);
    ProjectConfig::set('users.allowPublicRegistration', true);

    FakeOAuthProvider::reset();

    app(GeneralConfig::class)->oauthProviders([]);
});

function configureOAuthManagerProvider(array $config = []): void
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

describe('provider configuration', function () {
    test('provider groups accept ids uids and handles', function () {
        UserGroups::saveGroup($groupById = new UserGroup([
            'name' => 'ID Group',
            'handle' => 'id-group',
        ]));
        UserGroups::saveGroup($groupByUid = new UserGroup([
            'name' => 'UID Group',
            'handle' => 'uid-group',
        ]));
        UserGroups::saveGroup($groupByHandle = new UserGroup([
            'name' => 'Handle Group',
            'handle' => 'handle-group',
        ]));

        configureOAuthManagerProvider([
            'groups' => [
                (string) $groupById->id,
                $groupByUid->uid,
                $groupByHandle->handle,
            ],
        ]);

        $provider = app(OAuth::class)->getProviderDefinition('test');

        expect($provider)->not()->toBeNull()
            ->and($provider->groupIds)->toEqualCanonicalizing([
                $groupById->id,
                $groupByUid->id,
                $groupByHandle->id,
            ]);
    });

    test('provider groups accept a single configured value', function () {
        UserGroups::saveGroup($group = new UserGroup([
            'name' => 'Single Group',
            'handle' => 'single-group',
        ]));

        configureOAuthManagerProvider([
            'groups' => $group->handle,
        ]);

        $provider = app(OAuth::class)->getProviderDefinition('test');

        expect($provider)->not()->toBeNull()
            ->and($provider->groupIds)->toBe([$group->id]);
    });

    test('invalid configured groups make the provider unavailable', function () {
        config()->set('app.debug', false);

        configureOAuthManagerProvider([
            'groups' => ['missing-group'],
        ]);

        expect(app(OAuth::class)->getProviderDefinition('test'))->toBeNull()
            ->and(app(OAuth::class)->getLoginButtons())->toBe([]);
    });

    test('providers do not trust email fallback by default', function () {
        configureOAuthManagerProvider();

        expect(app(OAuth::class)->getProviderDefinition('test')->trustsEmail)->toBeFalse();
    });

    test('providers can trust email fallback when configured', function () {
        configureOAuthManagerProvider([
            'trustsEmail' => true,
        ]);

        expect(app(OAuth::class)->getProviderDefinition('test')->trustsEmail)->toBeTrue();
    });

    test('named driver shorthands can inherit credentials from services config', function (array $providers) {
        Config::set('services.github', [
            'client_id' => 'services-github-client',
            'client_secret' => 'services-github-secret',
        ]);

        app(GeneralConfig::class)->oauthProviders($providers);

        $provider = app(OAuth::class)->getProviderDefinition('github');

        expect($provider)->not()->toBeNull()
            ->and($provider->handle)->toBe('github')
            ->and($provider->driver)->toBe('github');

        startSession();
        request()->setLaravelSession(session()->driver());

        $redirectUrl = app(OAuth::class)
            ->buildProvider($provider)
            ->redirect()
            ->getTargetUrl();

        expect($redirectUrl)->toContain('github.com/login/oauth/authorize');
    })->with([
        'mapped shorthand' => [['github' => 'github']],
        'listed shorthand' => [['github']],
    ]);
});

describe('button rendering', function () {
    test('default login button uses a single cp trigger', function () {
        configureOAuthManagerProvider();

        $manager = app(OAuth::class);
        $provider = $manager->getProviderDefinition('test');

        expect($provider)->not()->toBeNull()
            ->and($manager->redirectPath($provider, true))
            ->toContain('/admin/oauth/test/redirect')
            ->not->toContain('/actions/auth/oauth/test')
            ->and($manager->callbackPath($provider, true))
            ->toContain('/oauth/test/callback?context=cp')
            ->and($manager->getLoginButtons(true)[0]->toHtml())
            ->toContain('Continue with Test OAuth')
            ->toContain('/admin/oauth/test/redirect');
    });

    test('custom button renderer is used', function () {
        configureOAuthManagerProvider([
            'buttonRenderer' => CustomButtonRenderer::class,
        ]);

        $buttons = app(OAuth::class)->getLoginButtons(true);

        expect($buttons)->toHaveCount(1)
            ->and($buttons[0]->toHtml())->toContain('oauth-custom');
    });

    test('custom group resolvers may return ids uids and handles', function () {
        UserGroups::saveGroup($groupById = new UserGroup([
            'name' => 'ID Group',
            'handle' => 'id-group-runtime',
        ]));
        UserGroups::saveGroup($groupByUid = new UserGroup([
            'name' => 'UID Group',
            'handle' => 'uid-group-runtime',
        ]));
        UserGroups::saveGroup($groupByHandle = new UserGroup([
            'name' => 'Handle Group',
            'handle' => 'handle-group-runtime',
        ]));

        CustomUserGroupResolver::$groups = [
            $groupById->id,
            $groupByUid->uid,
            $groupByHandle->handle,
        ];

        configureOAuthManagerProvider([
            'groupResolver' => CustomUserGroupResolver::class,
        ]);

        $manager = app(OAuth::class);
        $provider = $manager->getProviderDefinition('test');

        $groupIds = $manager->resolveGroupIds(
            $provider,
            FakeOAuthProvider::fakeUser([
                'id' => 'provider-user-groups',
            ]),
            User::findOne(),
            'provider-user-groups',
        );

        expect($groupIds)->toEqualCanonicalizing([
            $groupById->id,
            $groupByUid->id,
            $groupByHandle->id,
        ]);
    });
});
