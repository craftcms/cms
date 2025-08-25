<?php

use CraftCms\Cms\Http\Controllers\Dashboard\DashboardController;
use CraftCms\Cms\Http\Controllers\PluginsController;
use CraftCms\Cms\Http\Controllers\Utilities\UtilitiesController;
use CraftCms\Cms\Http\Middleware\RequireAdmin;

Route::get('dashboard', DashboardController::class);

Route::get('utilities', [UtilitiesController::class, 'index']);
Route::get('utilities/{id}', [UtilitiesController::class, 'show']);

/**
 * Routes that require admin, but do not require admin changes
 */
Route::middleware([
    RequireAdmin::class.':false',
])->group(function () {
    Route::get('settings/plugins', [PluginsController::class, 'index']);
    Route::get('settings/plugins/{handle}', [PluginsController::class, 'editSettings']);
});
