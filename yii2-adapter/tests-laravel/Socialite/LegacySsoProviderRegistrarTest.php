<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\OAuth\Provider;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use CraftCms\Yii2Adapter\OAuth\LegacySsoProviderRegistrar;
use CraftCms\Yii2Adapter\Tests\TestClasses\Auth\LegacyMarketingSsoProvider;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;

it('merges legacy sso providers into the oauth config', function() {
    bootstrapLegacySsoTest();

    configureLegacySsoProviders([
        'legacy-marketing' => [
            'class' => LegacyMarketingSsoProvider::class,
        ],
    ]);

    /** @var array<string, Provider> $oauthProviders */
    $oauthProviders = app(GeneralConfig::class)->oAuthProviders;

    expect($oauthProviders)->toHaveKey('legacy-marketing');
    expect($oauthProviders['legacy-marketing'])->toBeInstanceOf(Provider::class);
});

it('wraps legacy providers with legacy request urls', function() {
    bootstrapLegacySsoTest();

    configureLegacySsoProviders([
        'legacy-marketing' => [
            'class' => LegacyMarketingSsoProvider::class,
        ],
    ]);

    /** @var Provider $provider */
    $provider = app(GeneralConfig::class)->oAuthProviders['legacy-marketing'];

    $reflection = new ReflectionMethod($provider, 'getLoginUrl');
    $reflection->setAccessible(true);
    $loginUrl = $reflection->invoke($provider);

    expect($loginUrl)->toContain('provider=legacy-marketing');
    expect($loginUrl)->not()->toContain('auth/socialite/redirect');
});

/**
 * @param  array<string, array<string, mixed>>  $providers
 */
function configureLegacySsoProviders(array $providers): void
{
    $sso = \Craft::$app->getSso();
    $reflection = new ReflectionMethod($sso, 'setProviders');
    $reflection->setAccessible(true);
    $reflection->invoke($sso, $providers);

    app(LegacySsoProviderRegistrar::class)->mergeIntoConfig();
}

function bootstrapLegacySsoTest(): void
{
    app()->instance('cache', new CacheRepository(new ArrayStore()));
    Cache::clearResolvedInstances();

    Edition::set(Edition::Enterprise);
    app(GeneralConfig::class)->oAuthProviders = [];
}
