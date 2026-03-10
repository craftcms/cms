<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Tests\TestClasses\Auth\MarketingProviderDefinition;
use CraftCms\Yii2Adapter\Socialite\DuplicateSocialiteDriverException;
use CraftCms\Yii2Adapter\Socialite\LegacySsoDriverGuard;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

it('throws when a legacy handle conflicts with a core socialite provider', function () {
    Cms::config()->socialiteProviders = [
        'marketing' => [
            'name' => 'Marketing SSO',
        ],
    ];

    expect(fn () => app(LegacySsoDriverGuard::class)->assertLegacyProviderHandlesAvailable(['marketing']))
        ->toThrow(DuplicateSocialiteDriverException::class, 'Craft core socialiteProviders');
});

it('throws when a legacy handle conflicts with a core provider definition class', function () {
    Cms::config()->socialiteProviders = [
        MarketingProviderDefinition::class,
    ];

    expect(fn () => app(LegacySsoDriverGuard::class)->assertHandleAvailable('marketing'))
        ->toThrow(DuplicateSocialiteDriverException::class, 'Craft core socialiteProviders');
});

it('throws when a custom socialite driver is already registered', function () {
    Socialite::extend('legacy-marketing', fn ($app) => new class implements Provider
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
