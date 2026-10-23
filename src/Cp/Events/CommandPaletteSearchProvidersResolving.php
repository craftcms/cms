<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Events;

use CraftCms\Cms\Cp\CommandPalette\Contracts\SearchProvider;

/**
 * @event CommandPaletteSearchProvidersResolving The event that is triggered when registering the command palette’s search providers.
 *
 * ```php
 * use CraftCms\Cms\Cp\Events\CommandPaletteSearchProvidersResolving;
 * use Illuminate\Support\Facades\Event;
 *
 * Event::listen(function(CommandPaletteSearchProvidersResolving $event) {
 *     $event->providers[] = new MySearchProvider();
 * });
 * ```
 *
 * @since 6.0.0
 */
class CommandPaletteSearchProvidersResolving
{
    public function __construct(
        /** @var list<SearchProvider> */
        public array $providers,
    ) {}
}
