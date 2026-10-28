<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\CommandPalette\SearchProviders;

use CraftCms\Cms\Cp\CommandPalette\Actions;
use CraftCms\Cms\Cp\CommandPalette\CommandPaletteItem;
use CraftCms\Cms\Cp\CommandPalette\Contracts\SearchProvider;
use CraftCms\Cms\User\Contracts\CraftUser;

use function CraftCms\Cms\t;

/**
 * Finds the user's search-only actions — the large sets, like one per
 * locale, that the palette's initial list leaves out. The rest arrive with
 * that list, and the palette searches those itself, without waiting on this.
 *
 * @see Actions
 * @since 6.0.0
 */
readonly class ActionsSearchProvider implements SearchProvider
{
    public function __construct(
        private Actions $actions,
    ) {}

    public function getHandle(): string
    {
        return 'actions';
    }

    public function getLabel(): string
    {
        return t('Actions');
    }

    public function isAvailable(CraftUser $user): bool
    {
        return true;
    }

    public function search(string $query, CraftUser $user): iterable
    {
        return array_filter(
            $this->actions->all($user),
            fn (CommandPaletteItem $action) => $action->searchOnly && $action->matches($query),
        );
    }
}
