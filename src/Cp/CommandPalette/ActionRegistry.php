<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\CommandPalette;

use Illuminate\Container\Attributes\Singleton;

/**
 * Registers providers of the actions the command palette offers, alongside
 * Craft’s own.
 *
 * ```php
 * use CraftCms\Cms\Cp\CommandPalette\ActionRegistry;
 * use CraftCms\Cms\Cp\CommandPalette\CommandPaletteItem;
 * use CraftCms\Cms\User\Contracts\CraftUser;
 *
 * use function CraftCms\Cms\cp_url;
 *
 * public function boot(ActionRegistry $actions): void
 * {
 *     $actions->register('my-plugin', fn (CraftUser $user) => $user->isAdmin() ? [
 *         new CommandPaletteItem()
 *             ->id('my-plugin-clear-caches')
 *             ->label('Clear My Plugin caches')
 *             ->icon('broom')
 *             ->action([
 *                 'type' => 'http',
 *                 'url' => cp_url('my-plugin/clear-caches'),
 *                 'confirm' => 'Are you sure you want to clear My Plugin’s caches?',
 *             ]),
 *     ] : []);
 * }
 * ```
 *
 * Only return actions the user is allowed to run. The endpoints they call
 * must still check permissions themselves.
 *
 * @since 6.0.0
 */
#[Singleton]
class ActionRegistry extends ItemRegistry {}
