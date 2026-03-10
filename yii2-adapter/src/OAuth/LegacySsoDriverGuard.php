<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\OAuth;

use CraftCms\Cms\Auth\OAuth\OAuth;
use CraftCms\Cms\Config\GeneralConfig;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Manager;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use ReflectionProperty;

#[Singleton]
final readonly class LegacySsoDriverGuard
{
    /**
     * @param  string[]  $handles
     */
    public function assertLegacyProviderHandlesAvailable(array $handles): void
    {
        foreach ($handles as $handle) {
            $this->assertHandleAvailable($handle);
        }
    }

    public function assertHandleAvailable(string $handle): void
    {
        if (OAuth::configuredProviders($this->configuredProviders())->has($handle)) {
            throw new DuplicateSocialiteDriverException($handle, 'Craft core socialiteProviders');
        }

        if ($this->hasRegisteredCustomDriver($handle)) {
            throw new DuplicateSocialiteDriverException($handle, 'an existing Socialite driver');
        }
    }

    private function hasRegisteredCustomDriver(string $handle): bool
    {
        if (! app()->bound(SocialiteFactory::class)) {
            return false;
        }

        $socialite = app(SocialiteFactory::class);

        if (! $socialite instanceof Manager) {
            return false;
        }

        $customCreators = $this->managerProperty($socialite, 'customCreators');
        $drivers = $this->managerProperty($socialite, 'drivers');

        return isset($customCreators[$handle]) || isset($drivers[$handle]);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function configuredProviders(): array
    {
        if (! app()->bound(GeneralConfig::class)) {
            return [];
        }

        return app(GeneralConfig::class)->oAuthProviders;
    }

    /**
     * @return array<string, mixed>
     */
    private function managerProperty(Manager $manager, string $property): array
    {
        $reflection = new ReflectionProperty($manager, $property);
        $value = $reflection->getValue($manager);

        return is_array($value) ? $value : [];
    }
}
