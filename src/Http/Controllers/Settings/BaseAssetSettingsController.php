<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Support\Url;

use function CraftCms\Cms\t;

abstract class BaseAssetSettingsController
{
    /** @return list<NavItem> */
    protected function subnav(): array
    {
        $path = request()->craftPath();

        return [
            new NavItem()->label(t('Volumes'))->href(Url::cpUrl('settings/assets'))->selected($path === 'settings/assets'),
            new NavItem()->label(t('Image Transforms'))->href(Url::cpUrl('settings/assets/transforms'))->selected($path === 'settings/assets/transforms'),
            new NavItem()->label(t('Asset Transformers'))->href(Url::cpUrl('settings/assets/transformers'))->selected($path === 'settings/assets/transformers'),
        ];
    }
}
