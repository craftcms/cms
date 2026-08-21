<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings\Users;

use CraftCms\Cms\Cp\Data\NavItem;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\t;

abstract class BaseUserSettingsController
{
    /**
     * @return NavItem[]
     */
    protected function subnav(): array
    {
        $path = request()->craftPath();

        return [
            new NavItem()
                ->label(t('User Groups'))
                ->url(cp_url('settings/users'))
                ->selected($path === 'settings/users'),
            new NavItem()
                ->label(t('User Profile Fields'))
                ->url(cp_url('settings/users/fields'))
                ->selected($path === 'settings/users/fields'),
            new NavItem()
                ->label(t('Settings'))
                ->url(cp_url('settings/users/settings'))
                ->selected($path === 'settings/users/settings'),
        ];
    }

    /** @return list<array<string, string>> */
    protected function crumbs(string $title, ?string $url = null): array
    {
        return [
            ['label' => t('Settings'), 'href' => cp_url('settings')],
            array_filter(['label' => $title, 'href' => $url]),
        ];
    }
}
