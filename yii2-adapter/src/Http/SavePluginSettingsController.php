<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Http;

use CraftCms\Cms\Http\Controllers\PluginsController;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SavePluginSettingsController
{
    public function __invoke(Request $request, PluginsController $controller): Response
    {
        $handle = $request->validate([
            'pluginHandle' => ['required', 'string'],
        ])['pluginHandle'];

        return $controller->saveSettings($request, $handle);
    }
}
