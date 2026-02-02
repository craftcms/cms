<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Settings;
use Inertia\Inertia;

class SettingsIndexController
{
    public function __invoke(GeneralConfig $generalConfig, Settings $cpSettings)
    {
        return Inertia::render('SettingsIndexPage', [
            'readOnly' => ! $generalConfig->allowAdminChanges,
            'settings' => $cpSettings->all(),
        ]);
    }
}
