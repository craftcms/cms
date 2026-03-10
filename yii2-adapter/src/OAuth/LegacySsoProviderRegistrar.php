<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\OAuth;

use Craft;
use craft\auth\sso\ProviderInterface as LegacyProviderInterface;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use Illuminate\Container\Attributes\Singleton;

#[Singleton]
final readonly class LegacySsoProviderRegistrar
{
    public function __construct(
        private GeneralConfig $generalConfig,
    ) {
    }

    public function mergeIntoConfig(): void
    {
        if (!isset(Craft::$app) || !Edition::isAtLeast(Edition::Enterprise)) {
            return;
        }

        foreach (Craft::$app->getSso()->getProviders() as $provider) {
            if (!$provider instanceof LegacyProviderInterface || $provider->getHandle() === '') {
                continue;
            }

            $existingProvider = $this->generalConfig->oAuthProviders[$provider->getHandle()] ?? null;

            if (!is_null($existingProvider) && !$existingProvider instanceof LegacySsoProvider) {
                continue;
            }

            $this->generalConfig->oAuthProviders[$provider->getHandle()] = new LegacySsoProvider($provider);
        }
    }
}
