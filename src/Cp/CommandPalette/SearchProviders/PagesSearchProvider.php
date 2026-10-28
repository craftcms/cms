<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\CommandPalette\SearchProviders;

use CraftCms\Cms\Cp\CommandPalette\CommandPaletteItem;
use CraftCms\Cms\Cp\CommandPalette\Contracts\SearchProvider;
use CraftCms\Cms\Cp\CommandPalette\Pages;
use CraftCms\Cms\User\Contracts\CraftUser;

use function CraftCms\Cms\t;

/**
 * Finds the control panel and front-end pages the user can go to.
 *
 * @see Pages
 * @since 6.0.0
 */
readonly class PagesSearchProvider implements SearchProvider
{
    public function __construct(
        private Pages $pages,
    ) {}

    public function getHandle(): string
    {
        return 'pages';
    }

    public function getLabel(): string
    {
        return t('Pages');
    }

    public function isAvailable(CraftUser $user): bool
    {
        return true;
    }

    public function search(string $query, CraftUser $user): iterable
    {
        return array_filter(
            $this->pages->all($user),
            fn (CommandPaletteItem $page) => $page->matches($query),
        );
    }
}
