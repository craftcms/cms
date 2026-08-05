<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Settings;
use Inertia\Inertia;
use Inertia\Response;

use function CraftCms\Cms\t;

class SettingsIndexController
{
    public function __invoke(GeneralConfig $generalConfig, Settings $cpSettings): Response
    {
        return Inertia::render('settings/Index', [
            'title' => t('Settings'),
            'readOnly' => ! $generalConfig->allowAdminChanges,
            'crumbs' => [
                ['label' => t('Settings')],
            ],
            'settings' => $cpSettings->all(),
        ]);
    }
}
