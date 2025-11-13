<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\Dashboard\DashboardController;
use CraftCms\Cms\Http\Controllers\Entries\CreateEntryController;
use CraftCms\Cms\Http\Controllers\Entries\EntriesIndexController;
use CraftCms\Cms\Http\Controllers\FieldsController;
use CraftCms\Cms\Http\Controllers\FilesystemsController;
use CraftCms\Cms\Http\Controllers\InstallController;
use CraftCms\Cms\Http\Controllers\PluginsController;
use CraftCms\Cms\Http\Controllers\PluginStore\PluginStoreController;
use CraftCms\Cms\Http\Controllers\Settings\EntryTypesController;
use CraftCms\Cms\Http\Controllers\Settings\GeneralSettingsController;
use CraftCms\Cms\Http\Controllers\Settings\RoutesController;
use CraftCms\Cms\Http\Controllers\Settings\SectionsController;
use CraftCms\Cms\Http\Controllers\Settings\SitesController;
use CraftCms\Cms\Http\Controllers\Updates\UpdaterController;
use CraftCms\Cms\Http\Controllers\Utilities\UtilitiesController;
use CraftCms\Cms\Http\Middleware\HandleInertiaRequests;
use CraftCms\Cms\Http\Middleware\RequireAdmin;
use CraftCms\Cms\Http\Middleware\RequireAdminChanges;

/**
 * Admin requests that do not require a login
 */
\Illuminate\Support\Facades\Route::get('install', [InstallController::class, 'index'])
    ->middleware([HandleInertiaRequests::class]);

/**
 * Admin requests that require a login
 */
\Illuminate\Support\Facades\Route::middleware('auth')->group(function () {
    \Illuminate\Support\Facades\Route::get('dashboard', DashboardController::class);

    \Illuminate\Support\Facades\Route::get('utilities', [UtilitiesController::class, 'index']);
    \Illuminate\Support\Facades\Route::get('utilities/{id}', [UtilitiesController::class, 'show']);

    \Illuminate\Support\Facades\Route::middleware(RequireAdminChanges::class)->group(function () {
        \Illuminate\Support\Facades\Route::view('settings/addresses', 'craftcms::settings/addresses/_fields');
    });

    /**
     * Entries & Content
     */
    \Illuminate\Support\Facades\Route::get('entries', EntriesIndexController::class);
    \Illuminate\Support\Facades\Route::view('entries/{sectionHandle}', 'craftcms::entries.index');
    \Illuminate\Support\Facades\Route::get('entries/{section}/new', CreateEntryController::class);

    \Illuminate\Support\Facades\Route::get('content', EntriesIndexController::class);
    \Illuminate\Support\Facades\Route::view('content/{page}', 'craftcms::entries.index')->where('page', '[^\/]+');
    \Illuminate\Support\Facades\Route::view('content/{page}/{sectionHandle}', 'craftcms::entries.index')->where('page', '[^\/]+');
    \Illuminate\Support\Facades\Route::get('content/{section}/new', CreateEntryController::class);

    /**
     * Routes that require admin, but do not require admin changes
     */
    \Illuminate\Support\Facades\Route::middleware([
        RequireAdmin::class,
    ])->group(function () {
        // Entry types
        \Illuminate\Support\Facades\Route::get('settings/entry-types', [EntryTypesController::class, 'index']);
        \Illuminate\Support\Facades\Route::middleware(RequireAdminChanges::class)->get('settings/entry-types/new', [EntryTypesController::class, 'create']);
        \Illuminate\Support\Facades\Route::get('settings/entry-types/{entryTypeId}', [EntryTypesController::class, 'edit']);

        // Fields
        \Illuminate\Support\Facades\Route::get('settings/fields', [FieldsController::class, 'index']);
        \Illuminate\Support\Facades\Route::get('settings/fields/new', [FieldsController::class, 'edit']);
        \Illuminate\Support\Facades\Route::get('settings/fields/edit/{fieldId}', [FieldsController::class, 'edit']);

        // General
        \Illuminate\Support\Facades\Route::get('settings/general', [GeneralSettingsController::class, 'index']);

        // Plugins
        \Illuminate\Support\Facades\Route::get('settings/plugins', [PluginsController::class, 'index']);
        \Illuminate\Support\Facades\Route::get('settings/plugins/{handle}', [PluginsController::class, 'editSettings']);
        \Illuminate\Support\Facades\Route::get('plugin-store{any?}', [PluginStoreController::class, 'index'])->where('any', '.*');

        // Routes
        \Illuminate\Support\Facades\Route::get('settings/routes', [RoutesController::class, 'index']);

        // Sections
        \Illuminate\Support\Facades\Route::get('settings/sections', [SectionsController::class, 'index']);
        \Illuminate\Support\Facades\Route::middleware(RequireAdminChanges::class)->get('settings/sections/new', [SectionsController::class, 'create']);
        \Illuminate\Support\Facades\Route::get('settings/sections/{section}', [SectionsController::class, 'edit']);

        // Sites
        \Illuminate\Support\Facades\Route::get('settings/sites', [SitesController::class, 'index']);
        \Illuminate\Support\Facades\Route::middleware(RequireAdminChanges::class)->get('settings/sites/new', [SitesController::class, 'create']);
        \Illuminate\Support\Facades\Route::get('settings/sites/{site}', [SitesController::class, 'edit']);
    });

    \Illuminate\Support\Facades\Route::prefix('settings/filesystems')->group(function () {
        \Illuminate\Support\Facades\Route::middleware([
            RequireAdmin::class,
        ])->group(function () {
            \Illuminate\Support\Facades\Route::get('/', [FilesystemsController::class, 'index']);
            \Illuminate\Support\Facades\Route::get('new', [FilesystemsController::class, 'create']);
            \Illuminate\Support\Facades\Route::get('{handle}/edit', [FilesystemsController::class, 'edit']);
        });

        \Illuminate\Support\Facades\Route::middleware([
            RequireAdminChanges::class,
        ])->group(function () {
            \Illuminate\Support\Facades\Route::post('{handle}', [FilesystemsController::class, 'save']);
        });
    });

    \Illuminate\Support\Facades\Route::post('updates', [UpdaterController::class, 'index']);
});
