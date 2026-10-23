<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\CommandPalette;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Cp\CommandPalette\Contracts\SearchProvider;
use CraftCms\Cms\Cp\CommandPalette\SearchProviders\ActionsSearchProvider;
use CraftCms\Cms\Cp\CommandPalette\SearchProviders\ElementSearchProvider;
use CraftCms\Cms\Cp\CommandPalette\SearchProviders\GlossarySearchProvider;
use CraftCms\Cms\Cp\CommandPalette\SearchProviders\PagesSearchProvider;
use CraftCms\Cms\Cp\Events\CommandPaletteSearchProvidersResolving;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Container\Attributes\Scoped;

/**
 * The search providers the control panel’s command palette asks: Craft’s
 * own, plus whatever {@see CommandPaletteSearchProvidersResolving} listeners
 * add.
 *
 * The pages and actions its “Pages” and “Actions” providers search are
 * registered with {@see Pages} and {@see Actions}.
 *
 * @since 6.0.0
 */
#[Scoped]
class CommandPalette
{
    /** @var array<string, SearchProvider>|null */
    private ?array $searchProviders = null;

    /**
     * Every registered search provider, keyed by handle.
     *
     * @return array<string, SearchProvider>
     */
    public function getSearchProviders(): array
    {
        if ($this->searchProviders !== null) {
            return $this->searchProviders;
        }

        event($event = new CommandPaletteSearchProvidersResolving([
            app(PagesSearchProvider::class),
            app(ActionsSearchProvider::class),
            new ElementSearchProvider(Entry::class),
            new ElementSearchProvider(Asset::class),
            new ElementSearchProvider(User::class),
            app(GlossarySearchProvider::class),
        ]));

        $this->searchProviders = [];

        foreach ($event->providers as $provider) {
            $this->searchProviders[$provider->getHandle()] = $provider;
        }

        return $this->searchProviders;
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
