<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\CommandPalette;

use Illuminate\Container\Attributes\Singleton;

/**
 * Registers providers of the pages the command palette’s “Pages” search
 * provider can find, alongside Craft’s own.
 *
 * ```php
 * use CraftCms\Cms\Cp\CommandPalette\CommandPaletteItem;
 * use CraftCms\Cms\Cp\CommandPalette\PageRegistry;
 * use CraftCms\Cms\User\Contracts\CraftUser;
 *
 * use function CraftCms\Cms\cp_url;
 *
 * public function boot(PageRegistry $pages): void
 * {
 *     $pages->register('my-plugin', fn (CraftUser $user) => $user->can('accessPlugin-my-plugin') ? [
 *         new CommandPaletteItem()
 *             ->id('my-plugin-reports')
 *             ->label('Reports')
 *             ->description('My Plugin')
 *             ->href(cp_url('my-plugin/reports')),
 *     ] : []);
 * }
 * ```
 *
 * Only return pages the user can access.
 *
 * @since 6.0.0
 */
#[Singleton]
class PageRegistry extends ItemRegistry {}
