<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings\Users;

use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Cp\Data\NavItem;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
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
                ->href(cp_url('settings/users'))
                ->selected($path === 'settings/users'),
            new NavItem()
                ->label(t('User Profile Fields'))
                ->href(cp_url('settings/users/fields'))
                ->selected($path === 'settings/users/fields'),
            new NavItem()
                ->label(t('Settings'))
                ->href(cp_url('settings/users/settings'))
                ->selected($path === 'settings/users/settings'),
        ];
    }

    /**
     * The trail to the user settings. Screens with the subnav get its selected
     * item appended client-side; deeper screens append their own crumbs.
     *
     * @return list<ActionItem>
     */
    protected function crumbs(): array
    {
        return [
            new ActionItem()->label(t('Settings'))->href(cp_url('settings')),
            new ActionItem()->label(t('Users'))->href(cp_url('settings/users')),
        ];
    }
}
