<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuth;

use CraftCms\Cms\Auth\OAuth\Exceptions\SocialiteProviderNotFoundException;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

#[Singleton]
final readonly class OAuth
{
    public function __construct(
        private GeneralConfig $generalConfig,
    ) {}

    /**
     * @param  array<int|string, mixed>  $providers
     *
     * @return Collection<string, Provider>
     */
    public static function configuredProviders(array $providers): Collection
    {
        return collect($providers)
            ->map(fn (mixed $config, mixed $handle = null) => self::configuredProvider($config, is_string($handle) ? $handle : null))
            ->filter(fn (mixed $provider) => $provider instanceof Provider && $provider->handle !== '')
            ->keyBy(fn(Provider $provider) => $provider->handle);
    }

    /**
     * @return Collection<string, Provider>
     */
    public function getProviders(): Collection
    {
        if (! Edition::get()->oAuthAvailable()) {
            return collect();
        }

        return self::configuredProviders($this->generalConfig->oAuthProviders);
    }

    public function getProvider(string $handle): Provider
    {
        $provider = $this->getProviders()->get($handle);

        if (! $provider instanceof Provider) {
            throw new SocialiteProviderNotFoundException($handle);
        }

        return $provider;
    }

    /**
     * @return Collection<int, Provider>
     */
    public function getLoginProviders(): Collection
    {
        return $this->getProviders()->values();
    }

    private static function configuredProvider(mixed $config, ?string $handle = null): ?Provider
    {
        if ($config instanceof Provider) {
            return $config;
        }

        if (is_string($config) && is_a($config, Provider::class, true)) {
            return self::instantiateProvider($config, $handle);
        }

        if (! is_array($config)) {
            return null;
        }

        $class = Arr::pull($config, 'class') ?? Provider::class;
        $handle ??= Arr::get($config, 'handle');

        if (! $handle) {
            return null;
        }

        return self::instantiateProvider($class, $handle, $config);
    }

    /**
     * @param  class-string<Provider>  $class
     * @param  array<string, mixed>  $config
     */
    private static function instantiateProvider(string $class, ?string $handle = null, array $config = []): ?Provider
    {
        $provider = app()->make($class, Arr::whereNotNull([
            'handle' => $handle ?? Arr::pull($config, 'handle'),
            'config' => $config,
        ]));

        return $provider instanceof Provider ? $provider : null;
    }
}
