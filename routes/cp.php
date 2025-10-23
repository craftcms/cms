<?php

use CraftCms\Cms\Http\Controllers\Dashboard\DashboardController;
use CraftCms\Cms\Http\Controllers\FieldsController;
use CraftCms\Cms\Http\Controllers\FilesystemsController;
use CraftCms\Cms\Http\Controllers\InstallController;
use CraftCms\Cms\Http\Controllers\PluginsController;
use CraftCms\Cms\Http\Controllers\PluginStore\PluginStoreController;
use CraftCms\Cms\Http\Controllers\Settings\GeneralSettingsController;
use CraftCms\Cms\Http\Controllers\Settings\SectionsController;
use CraftCms\Cms\Http\Controllers\Settings\SitesController;
use CraftCms\Cms\Http\Controllers\Updates\UpdaterController;
use CraftCms\Cms\Http\Controllers\Utilities\UtilitiesController;
use CraftCms\Cms\Http\Middleware\RequireAdmin;
use CraftCms\Cms\Http\Middleware\RequireAdminChanges;

/**
 * Admin requests that do not require a login
 */
Route::get('install', [InstallController::class, 'index']);

/**
 * Admin requests that require a login
 */
Route::middleware('auth')->group(function () {
    Route::get('dashboard', DashboardController::class);

    Route::get('utilities', [UtilitiesController::class, 'index']);
    Route::get('utilities/{id}', [UtilitiesController::class, 'show']);

    Route::middleware(RequireAdminChanges::class)->group(function () {
        Route::view('settings/addresses', 'craftcms::settings/addresses/_fields');
    });

    /**
     * Routes that require admin, but do not require admin changes
     */
    Route::middleware([
        RequireAdmin::class,
    ])->group(function () {
        // Fields
        Route::get('settings/fields', [FieldsController::class, 'index']);
        Route::get('settings/fields/new', [FieldsController::class, 'edit']);
        Route::get('settings/fields/edit/{fieldId}', [FieldsController::class, 'edit']);

        // General
        Route::get('settings/general', [GeneralSettingsController::class, 'index']);

        // Plugins
        Route::get('settings/plugins', [PluginsController::class, 'index']);
        Route::get('settings/plugins/{handle}', [PluginsController::class, 'editSettings']);
        Route::get('plugin-store{any?}', [PluginStoreController::class, 'index'])->where('any', '.*');

        // Sections
        Route::get('settings/sections', [SectionsController::class, 'index']);
        Route::middleware(RequireAdminChanges::class)->get('settings/sections/new', [SectionsController::class, 'create']);
        Route::get('settings/sections/{section}', [SectionsController::class, 'edit']);

        // Sites
        Route::get('settings/sites', [SitesController::class, 'index']);
        Route::middleware(RequireAdminChanges::class)->get('settings/sites/new', [SitesController::class, 'create']);
        Route::get('settings/sites/{site}', [SitesController::class, 'edit']);
    });

    Route::prefix('settings/filesystems')->group(function () {
        Route::middleware([
            RequireAdmin::class,
        ])->group(function () {
            Route::get('/', [FilesystemsController::class, 'index']);
            Route::get('new', [FilesystemsController::class, 'create']);
            Route::get('{handle}/edit', [FilesystemsController::class, 'edit']);
        });

        Route::middleware([
            RequireAdminChanges::class,
        ])->group(function () {
            Route::post('{handle}', [FilesystemsController::class, 'save']);
        });
    });

    Route::post('updates', [UpdaterController::class, 'index']);
});
