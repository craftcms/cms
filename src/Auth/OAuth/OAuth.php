<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuth;

use CraftCms\Cms\Auth\OAuth\Exceptions\SocialiteProviderNotFoundException;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Uri;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;

#[Singleton]
final readonly class OAuth
{
    public function __construct(
        private GeneralConfig $generalConfig,
        private SocialiteFactory $socialite,
    ) {}

    /**
     * @param  array<int|string, mixed>  $providers
     * @return Collection<string, ProviderDefinition>
     */
    public static function configuredProviders(array $providers): Collection
    {
        return collect($providers)
            ->map(fn (mixed $config, mixed $handle = null) => self::configuredProvider($config, is_string($handle) ? $handle : null))
            ->filter(fn (mixed $provider) => $provider instanceof ProviderDefinition && $provider->handle !== '')
            ->keyBy(fn (ProviderDefinition $provider) => $provider->handle);
    }

    /**
     * @return Collection<string, ProviderDefinition>
     */
    public function getProviders(): Collection
    {
        if (! Edition::get()->oAuthAvailable()) {
            return collect();
        }

        return self::configuredProviders($this->generalConfig->socialiteProviders);
    }

    public function getProvider(string $handle): ProviderDefinition
    {
        $provider = $this->getProviders()->get($handle);

        if (! $provider instanceof ProviderDefinition) {
            throw new SocialiteProviderNotFoundException($handle);
        }

        return $provider;
    }

    /**
     * @return Collection<int, ProviderDefinition>
     */
    public function getLoginProviders(): Collection
    {
        return $this->getProviders()->values();
    }

    public function hasLoginProviders(): bool
    {
        return $this->getLoginProviders()->isNotEmpty();
    }

    public function redirect(string $handle): mixed
    {
        $provider = $this->getProvider($handle);

        return $this->driver($provider)->redirect();
    }

    public function user(string $handle): Profile
    {
        $provider = $this->getProvider($handle);

        return Profile::fromUser(
            $provider->handle,
            $this->driver($provider)->user(),
        );
    }

    private function driver(ProviderDefinition $provider): mixed
    {
        Config::set("services.{$provider->driver}", $this->credentials($provider));

        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = $this->socialite->driver($provider->driver);

        if ($provider->stateless) {
            $driver = $driver->stateless();
        }

        if (! empty($provider->scopes)) {
            $driver = $driver->scopes($provider->scopes);
        }

        if ($provider->with !== []) {
            $driver = $driver->with($provider->with);
        }

        return $driver;
    }

    /**
     * @return array<string, mixed>
     */
    private function credentials(ProviderDefinition $provider): array
    {
        $credentials = array_merge(
            (array) Config::get("services.{$provider->driver}", []),
            (array) Config::get("services.{$provider->handle}", []),
            Arr::whereNotNull([
                'client_id' => $provider->clientId,
                'client_secret' => $provider->clientSecret,
                'redirect' => $this->callbackUrl($provider),
            ]),
        );

        $credentials['redirect'] ??= $this->callbackUrl($provider);

        return $credentials;
    }

    private function callbackUrl(ProviderDefinition $provider): string
    {
        $isCpRequest = request()->boolean('cp');

        $url = $provider->redirectUrl ?? route('craft.auth.socialite.callback', [
            'provider' => $provider->handle,
            ...($isCpRequest ? ['cp' => 1] : []),
        ]);

        if (! $isCpRequest) {
            return $url;
        }

        return Uri::of($url)
            ->withQueryIfMissing(['cp' => 1])
            ->value();
    }

    private static function configuredProvider(mixed $config, ?string $handle = null): ?ProviderDefinition
    {
        if ($config instanceof ProviderDefinition) {
            return $config;
        }

        if (is_string($config) && is_a($config, ProviderDefinition::class, true)) {
            return self::instantiateProvider($config, $handle);
        }

        if (! is_array($config)) {
            return null;
        }

        if (
            isset($config['class']) &&
            is_string($config['class']) &&
            is_a($config['class'], ProviderDefinition::class, true)
        ) {
            $class = Arr::pull($config, 'class');

            return self::instantiateProvider($class, $handle, $config);
        }

        $handle ??= Arr::get($config, 'handle');

        if (! $handle) {
            return null;
        }

        return new ProviderDefinition($handle, $config);
    }

    /**
     * @param  class-string<ProviderDefinition>  $class
     * @param  array<string, mixed>  $config
     */
    private static function instantiateProvider(string $class, ?string $handle = null, array $config = []): ?ProviderDefinition
    {
        $provider = app()->make($class, Arr::whereNotNull([
            'handle' => $handle,
            ...$config,
        ]));

        return $provider instanceof ProviderDefinition ? $provider : null;
    }
}
