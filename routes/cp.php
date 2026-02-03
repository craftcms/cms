<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\Auth\LoginController;
use CraftCms\Cms\Http\Controllers\Auth\SetPasswordController;
use CraftCms\Cms\Http\Controllers\Auth\TwoFactorAuthenticationController;
use CraftCms\Cms\Http\Controllers\Auth\VerifyEmailController;
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
use CraftCms\Cms\Http\Controllers\Settings\SettingsIndexController;
use CraftCms\Cms\Http\Controllers\Settings\SiteGroupsController;
use CraftCms\Cms\Http\Controllers\Settings\SitesController;
use CraftCms\Cms\Http\Controllers\Settings\UserGroupsController;
use CraftCms\Cms\Http\Controllers\Settings\UserSettingsController;
use CraftCms\Cms\Http\Controllers\Updates\UpdaterController;
use CraftCms\Cms\Http\Controllers\Users\AddressesController;
use CraftCms\Cms\Http\Controllers\Users\PasskeysController;
use CraftCms\Cms\Http\Controllers\Users\PasswordController;
use CraftCms\Cms\Http\Controllers\Users\PermissionsController;
use CraftCms\Cms\Http\Controllers\Users\PreferencesController;
use CraftCms\Cms\Http\Controllers\Users\UsersController;
use CraftCms\Cms\Http\Controllers\Utilities\UtilitiesController;
use CraftCms\Cms\Http\Middleware\HandleInertiaRequests;
use CraftCms\Cms\Http\Middleware\RequireAdmin;
use CraftCms\Cms\Http\Middleware\RequireAdminChanges;
use CraftCms\Cms\Http\Middleware\RequireEdition;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/**
 * Admin requests that do not require a login
 */
Route::get('install', [InstallController::class, 'index']);

Route::get(CpAuthPath::Login->value, [LoginController::class, 'showLogin']);
Route::get(CpAuthPath::TwoFactorChallenge->value, [TwoFactorAuthenticationController::class, 'showForm']);
Route::get(CpAuthPath::SetPassword->value, [SetPasswordController::class, 'show']);
Route::post(CpAuthPath::SetPassword->value, [SetPasswordController::class, 'store']);
Route::get(CpAuthPath::VerifyEmail->value, [VerifyEmailController::class, 'show']);
Route::post(CpAuthPath::VerifyEmail->value, [VerifyEmailController::class, 'store']);

/**
 * Admin requests that require a login
 */
Route::middleware(['auth:craft', 'can:accessCp'])->group(function () {
    Route::get(CpAuthPath::Logout->value, [LoginController::class, 'logout']);
    Route::get('dashboard', DashboardController::class);

    Route::get('utilities', [UtilitiesController::class, 'index']);
    Route::get('utilities/{id}/{extra?}', [UtilitiesController::class, 'show'])
        ->middleware([HandleInertiaRequests::class])
        ->where('extra', '.*')
        ->name('utilities.show');

    Route::middleware(RequireAdminChanges::class)->group(function () {
        Route::view('settings/addresses', 'craftcms::settings/addresses/_fields');
    });

    /**
     * Entries & Content
     */
    Route::get('entries', EntriesIndexController::class);
    Route::view('entries/{sectionHandle}', 'craftcms::entries.index');
    Route::get('entries/{section}/new', CreateEntryController::class);

    Route::get('content', EntriesIndexController::class);
    Route::view('content/{page}', 'craftcms::entries.index')->where('page', '[^\/]+');
    Route::view('content/{page}/{sectionHandle}', 'craftcms::entries.index')->where('page', '[^\/]+');
    Route::get('content/{section}/new', CreateEntryController::class);

    /**
     * Users
     */
    Route::get('myaccount', [UsersController::class, 'edit']);
    Route::get('myaccount/addresses', [AddressesController::class, 'index']);
    Route::get('myaccount/permissions', [PermissionsController::class, 'index']);
    Route::get('myaccount/passkeys', [PasskeysController::class, 'index']);
    Route::get('myaccount/password', [PasswordController::class, 'index']);
    Route::get('myaccount/preferences', [PreferencesController::class, 'index']);

    Route::middleware([
        RequireEdition::class.':'.Edition::Team->value,
    ])->group(function () {
        Route::get('users/new', [UsersController::class, 'create']);
        Route::get('users/{userId}', [UsersController::class, 'edit'])->whereNumber('userId');
        Route::get('users/{userId}/addresses', [AddressesController::class, 'index'])->whereNumber('userId');
        Route::get('users/{userId}/permissions', [PermissionsController::class, 'index']);
    });

    Route::get('users/{slug?}', [UsersController::class, 'index']);

    /**
     * Routes that require admin, but do not require admin changes
     */
    Route::middleware([
        RequireAdmin::class,
    ])->group(function () {
        // Index page
        Route::get('settings', SettingsIndexController::class)
            ->name('settings.index');

        // Entry types
        Route::get('settings/entry-types', [EntryTypesController::class, 'index']);
        Route::middleware(RequireAdminChanges::class)->get('settings/entry-types/new', [EntryTypesController::class, 'create']);
        Route::get('settings/entry-types/{entryType}', [EntryTypesController::class, 'edit']);

        // Fields
        Route::get('settings/fields', [FieldsController::class, 'index']);
        Route::middleware(RequireAdminChanges::class)->get('settings/fields/new', [FieldsController::class, 'create']);
        Route::get('settings/fields/edit/{fieldId}', [FieldsController::class, 'edit']);

        // General
        Route::get('settings/general', [GeneralSettingsController::class, 'index'])
            ->name('settings.general.index');
        Route::post('settings/general', [GeneralSettingsController::class, 'store'])
            ->middleware([RequireAdminChanges::class])
            ->name('settings.general.store');

        // Plugins
        Route::get('settings/plugins', [PluginsController::class, 'index']);
        Route::get('settings/plugins/{handle}', [PluginsController::class, 'editSettings']);
        Route::get('plugin-store{any?}', [PluginStoreController::class, 'index'])->where('any', '.*');

        // Rebrand
        // @TODO: Remove when rebrand assets are refactored
        Route::get('rebrand/{type}/{filename}', function (string $type, string $filename) {
            abort_unless(in_array($type, ['icon', 'logo']), 404);

            $file = Storage::disk('rebrand')->path("$type/$filename");

            abort_unless(file_exists($file), 404);

            return response()->file($file);
        })->where('filename', '.*');

        // Routes
        Route::get('settings/routes', [RoutesController::class, 'index']);

        // Sections
        Route::get('settings/sections', [SectionsController::class, 'index']);
        Route::middleware(RequireAdminChanges::class)->get('settings/sections/new', [SectionsController::class, 'create']);
        Route::get('settings/sections/{section}', [SectionsController::class, 'edit']);

        // Sites
        Route::get('settings/sites', [SitesController::class, 'index'])
            ->name('settings.sites.index');
        Route::middleware(RequireAdminChanges::class)
            ->group(function () {
                Route::get('settings/sites/new', [SitesController::class, 'create']);
                Route::post('settings/sites/reorder', [SitesController::class, 'reorder']);
                Route::post('settings/sites', [SitesController::class, 'store']);
                Route::delete('settings/sites/{site}', [SitesController::class, 'destroy']);
            });
        Route::get('settings/sites/{site}', [SitesController::class, 'edit']);

        // Site Groups
        Route::middleware(RequireAdminChanges::class)
            ->group(function () {
                Route::post('settings/site-groups', [SiteGroupsController::class, 'store']);
                Route::delete('settings/site-groups/{groupId}', [SiteGroupsController::class, 'destroy'])
                    ->name('settings.site-groups.destroy');
            });

        // User groups
        Route::middleware([RequireEdition::class.':'.Edition::Team->value])->group(function () {
            Route::get('settings/users', [UserGroupsController::class, 'index']);
            Route::middleware([
                RequireEdition::class.':'.Edition::Pro->value,
                RequireAdminChanges::class,
            ])->group(function () {
                Route::get('settings/users/groups/new', [UserGroupsController::class, 'create']);
            });
            Route::get('settings/users/groups/{userGroup}', [UserGroupsController::class, 'edit']);
        });

        // User settings
        Route::get('settings/users/settings', [UserSettingsController::class, 'index']);
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
