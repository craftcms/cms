<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Events;

use CraftCms\Cms\Cp\CommandPalette\CommandPaletteItem;
use CraftCms\Cms\User\Contracts\CraftUser;

/**
 * @event CommandPaletteActionsResolving The event that is triggered when gathering the actions the command palette offers a user.
 *
 * Only add actions the user is allowed to run. The endpoints they call must
 * still check permissions themselves.
 *
 * ```php
 * use CraftCms\Cms\Cp\CommandPalette\CommandPaletteItem;
 * use CraftCms\Cms\Cp\Events\CommandPaletteActionsResolving;
 * use Illuminate\Support\Facades\Event;
 *
 * use function CraftCms\Cms\cp_url;
 *
 * Event::listen(function(CommandPaletteActionsResolving $event) {
 *     if ($event->user->isAdmin()) {
 *         $event->actions[] = new CommandPaletteItem()
 *             ->id('my-plugin-clear-caches')
 *             ->label('Clear My Plugin caches')
 *             ->icon('broom')
 *             ->action([
 *                 'type' => 'http',
 *                 'url' => cp_url('my-plugin/clear-caches'),
 *                 'confirm' => 'Are you sure you want to clear My Plugin’s caches?',
 *             ]);
 *     }
 * });
 * ```
 *
 * @since 6.0.0
 */
class CommandPaletteActionsResolving
{
    public function __construct(
        public readonly CraftUser $user,
        /** @var list<CommandPaletteItem> */
        public array $actions,
    ) {}
}
