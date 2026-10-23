<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Events;

use CraftCms\Cms\Cp\CommandPalette\CommandPaletteItem;
use CraftCms\Cms\User\Contracts\CraftUser;

/**
 * @event CommandPalettePagesResolving The event that is triggered when gathering the pages the command palette’s “Pages” search provider can find for a user.
 *
 * Only add pages the user can access.
 *
 * ```php
 * use CraftCms\Cms\Cp\CommandPalette\CommandPaletteItem;
 * use CraftCms\Cms\Cp\Events\CommandPalettePagesResolving;
 * use Illuminate\Support\Facades\Event;
 *
 * use function CraftCms\Cms\cp_url;
 *
 * Event::listen(function(CommandPalettePagesResolving $event) {
 *     if ($event->user->can('accessPlugin-my-plugin')) {
 *         $event->pages[] = new CommandPaletteItem()
 *             ->id('my-plugin-reports')
 *             ->label('Reports')
 *             ->description('My Plugin')
 *             ->href(cp_url('my-plugin/reports'));
 *     }
 * });
 * ```
 *
 * @since 6.0.0
 */
class CommandPalettePagesResolving
{
    public function __construct(
        public readonly CraftUser $user,
        /** @var list<CommandPaletteItem> */
        public array $pages,
    ) {}
}
