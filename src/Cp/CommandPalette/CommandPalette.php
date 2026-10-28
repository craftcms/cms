<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\CommandPalette;

use CraftCms\Cms\Cp\CommandPalette\Contracts\SearchProvider;
use CraftCms\Cms\User\Contracts\CraftUser;
use Illuminate\Container\Attributes\Scoped;

/**
 * The search providers the control panel’s command palette asks, as
 * registered with {@see SearchProviderRegistry}.
 *
 * The pages and actions its “Pages” and “Actions” providers search come
 * from {@see Pages} and {@see Actions}, which add those registered with
 * {@see PageRegistry} and {@see ActionRegistry} to Craft’s own.
 *
 * @since 6.0.0
 */
#[Scoped]
class CommandPalette
{
    /** @var array<string, SearchProvider>|null */
    private ?array $searchProviders = null;

    public function __construct(
        private readonly SearchProviderRegistry $registry,
    ) {}

    /**
     * Every registered search provider, keyed by handle.
     *
     * @return array<string, SearchProvider>
     */
    public function getSearchProviders(): array
    {
        return $this->searchProviders ??= $this->registry->providers();
    }

    /**
     * The search providers the user can use, keyed by handle.
     *
     * @return array<string, SearchProvider>
     */
    public function getAvailableSearchProviders(CraftUser $user): array
    {
        return array_filter(
            $this->getSearchProviders(),
            fn (SearchProvider $provider) => $provider->isAvailable($user),
        );
    }

    public function getSearchProvider(string $handle): ?SearchProvider
    {
        return $this->getSearchProviders()[$handle] ?? null;
    }
}
