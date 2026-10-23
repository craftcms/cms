<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\CommandPalette\Contracts;

use CraftCms\Cms\Cp\CommandPalette\CommandPaletteItem;
use CraftCms\Cms\Cp\Events\CommandPaletteSearchProvidersResolving;
use CraftCms\Cms\User\Contracts\CraftUser;

/**
 * Searches one kind of thing for the command palette.
 *
 * The palette asks every available provider in parallel, one request each, as
 * the user types, and merges what comes back into a single list ranked by how
 * closely each item's label matches the query. So a provider only has to find
 * matches; it doesn't have to rank them against anyone else's.
 *
 * Register one with the {@see CommandPaletteSearchProvidersResolving}
 * event.
 *
 * @since 6.0.0
 */
interface SearchProvider
{
    /** A unique, URL-safe handle the palette requests results by. */
    public function getHandle(): string;

    public function getLabel(): string;

    /** Whether the user can use this provider at all. */
    public function isAvailable(CraftUser $user): bool;

    /**
     * The items matching the query, best first.
     *
     * @return iterable<CommandPaletteItem>
     */
    public function search(string $query, CraftUser $user): iterable;
}
