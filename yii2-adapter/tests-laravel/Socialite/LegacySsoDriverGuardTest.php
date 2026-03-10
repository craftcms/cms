<?php

declare(strict_types=1);

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Tests\TestClasses\Auth\MarketingProvider;
use CraftCms\Yii2Adapter\OAuth\DuplicateSocialiteDriverException;
use CraftCms\Yii2Adapter\OAuth\LegacySsoDriverGuard;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\SocialiteManager;
use Laravel\Socialite\Two\User as SocialiteUser;

beforeEach(function () {
    if (! app()->bound('config')) {
        app()->instance('config', new ConfigRepository([
            'craft.general.socialiteProviders' => [],
            'services' => [],
        ]));
    }

    if (! app()->bound(SocialiteFactory::class)) {
        app()->singleton(SocialiteFactory::class, fn ($app) => new SocialiteManager($app));
    }
});

it('throws when a legacy handle conflicts with a core socialite provider', function () {
    $config = new class extends GeneralConfig
    {
        public function __construct() {}
    };
    $config->oAuthProviders = [
        'marketing' => [
            'name' => 'Marketing SSO',
        ],
    ];

    app()->instance(GeneralConfig::class, $config);

    expect(fn () => app(LegacySsoDriverGuard::class)->assertLegacyProviderHandlesAvailable(['marketing']))
        ->toThrow(DuplicateSocialiteDriverException::class, 'Craft core socialiteProviders');
});

it('throws when a legacy handle conflicts with a core provider definition class', function () {
    $config = new class extends GeneralConfig
    {
        public function __construct() {}
    };
    $config->oAuthProviders = [
        MarketingProvider::class,
    ];

    app()->instance(GeneralConfig::class, $config);

    expect(fn () => app(LegacySsoDriverGuard::class)->assertHandleAvailable('marketing'))
        ->toThrow(DuplicateSocialiteDriverException::class, 'Craft core socialiteProviders');
});

it('throws when a custom socialite driver is already registered', function () {
    app(SocialiteFactory::class)->extend('legacy-marketing', fn ($app) => new class implements Provider
    {
        public function redirect()
        {
            return new RedirectResponse('/');
        }

        public function user()
        {
            return new SocialiteUser;
        }
    });

    expect(fn () => app(LegacySsoDriverGuard::class)->assertHandleAvailable('legacy-marketing'))
        ->toThrow(DuplicateSocialiteDriverException::class, 'existing Socialite driver');
});
