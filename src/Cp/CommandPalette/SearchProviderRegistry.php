<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\CommandPalette;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Cp\CommandPalette\Contracts\SearchProvider;
use CraftCms\Cms\Cp\CommandPalette\SearchProviders\ActionsSearchProvider;
use CraftCms\Cms\Cp\CommandPalette\SearchProviders\ElementSearchProvider;
use CraftCms\Cms\Cp\CommandPalette\SearchProviders\PagesSearchProvider;
use CraftCms\Cms\Cp\CommandPalette\SearchProviders\ReleaseSearchProvider;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Registers the search providers the command palette asks: Craft’s own, plus
 * any others.
 *
 * ```php
 * use CraftCms\Cms\Cp\CommandPalette\SearchProviderRegistry;
 * use CraftCms\Cms\Cp\CommandPalette\SearchProviders\ElementSearchProvider;
 * use CraftCms\Cms\User\Contracts\CraftUser;
 *
 * public function boot(SearchProviderRegistry $searchProviders): void
 * {
 *     $searchProviders->register(
 *         MySearchProvider::class,
 *         new ElementSearchProvider(Product::class, fn (CraftUser $user) => $user->can('commerce-manageProducts')),
 *     );
 * }
 * ```
 *
 * A provider can be registered as an instance, or as a class name that's
 * resolved from the container each time the palette asks, so it can depend
 * on request-scoped services.
 *
 * @since 6.0.0
 */
#[Singleton]
class SearchProviderRegistry
{
    /** @var list<SearchProvider|class-string<SearchProvider>> */
    private array $providers = [];

    /** @var array<string, true> */
    private array $removedHandles = [];

    public function __construct(
        private readonly Container $container,
    ) {
        $this->register(
            PagesSearchProvider::class,
            ActionsSearchProvider::class,
            new ElementSearchProvider(Entry::class),
            new ElementSearchProvider(Asset::class),
            new ElementSearchProvider(User::class),
            ReleaseSearchProvider::class,
        );
    }

    /** @param SearchProvider|class-string<SearchProvider> ...$providers */
    public function register(SearchProvider|string ...$providers): void
    {
        foreach ($providers as $provider) {
            if (is_string($provider) && ! is_a($provider, SearchProvider::class, true)) {
                throw new InvalidArgumentException(sprintf('Search provider [%s] must implement %s.', $provider, SearchProvider::class));
            }
        }

        array_push($this->providers, ...$providers);
    }

    /** Leaves out the providers with these handles, Craft’s included. */
    public function remove(string ...$handles): void
    {
        foreach ($handles as $handle) {
            $this->removedHandles[$handle] = true;
        }
    }

    /**
     * Every registered search provider, keyed by handle.
     *
     * @return array<string, SearchProvider>
     */
    public function providers(): array
    {
        $providers = [];

        foreach ($this->providers as $provider) {
            $provider = is_string($provider) ? $this->container->make($provider) : $provider;
            $handle = $provider->getHandle();

            if (isset($this->removedHandles[$handle])) {
                continue;
            }

            if (isset($providers[$handle])) {
                throw new InvalidArgumentException("Search provider handle [$handle] is registered more than once.");
            }

            $providers[$handle] = $provider;
        }

        return $providers;
    }
}
